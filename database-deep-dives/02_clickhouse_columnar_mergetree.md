# ClickHouse Columnar Storage & MergeTree Mechanics

> **Module:** Database Deep Dives (Topic 2.2)  
> **Source Mapping:** `backend-roadmap.md` (Level 29: #580–#585)

---

## 💡 1. Conceptual Blueprint & First Principles

OLTP systems (PostgreSQL, MySQL) use row-oriented storage optimized for point lookups. 
OLAP systems like **ClickHouse** use **Columnar Storage**.

### First-Principles Mechanics (CPU/OS)
1. **CPU Cache & SIMD:** Reading contiguous memory (an array of integers) allows the CPU to fetch multiple values into L1/L2 cache in a single hardware cycle. ClickHouse uses **Vectorized Execution**, leveraging AVX-512 SIMD (Single Instruction, Multiple Data) instructions to sum hundreds of values in a single clock cycle.
2. **Extreme Compression:** Because a column file contains identical data types (e.g., all timestamps), algorithms like LZ4 or ZSTD achieve massive compression (up to 10x). This shifts the bottleneck from Disk I/O to CPU decompression, which is vastly faster.

## 🔬 2. Under-the-Hood: MergeTree Engine & Sparse Indexing

Data written to ClickHouse is grouped into immutable "Parts" on disk. Background threads constantly merge these parts (LSM-tree style).

```mermaid
graph TD
    subgraph ["ClickHouse Sparse Indexing (Granule = 8192)"]
        Index["primary.idx (RAM)"]
        Mark["Marks (.mrk file)"]
        Col["Data (.bin file)"]
        
        Index -- "Binary Search" --> Mark
        Mark -- "Byte Offset" --> Col
    end
```

A **Sparse Index** indexes one row per *Granule* (default 8,192 rows). 
- To find `date = 2026-08-15`, ClickHouse binary-searches `primary.idx`.
- It uses `.mrk` files to find exact byte offsets in `.bin` files.
- It streams only those compressed blocks into RAM.

---

## 🏢 3. Real-World Production Example (Uber & Cloudflare)

**Cloudflare** processes over 30 million DNS queries per second. They use ClickHouse to store logs.
- **Trade-off:** ClickHouse cannot handle millions of single-row `INSERT`s per second due to heavy Part-merging overhead (too many small files). 
- **Architecture Solution:** Cloudflare buffers data in Kafka. Microservices pull from Kafka and execute **batch inserts** into ClickHouse (e.g., 100,000 rows per batch, every 1-2 seconds).

## 💻 4. Production Code & Benchmarks

**ClickHouse MergeTree DDL (Go/Python Native Integration):**
```sql
CREATE TABLE events (
    event_time DateTime,
    user_id UInt64,
    event_type LowCardinality(String), -- Dictionary encoding for fast scans
    revenue Float32
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (user_id, event_time)
SETTINGS index_granularity = 8192;
```

### Exact CLI Benchmark Command (`clickhouse-benchmark`)
```bash
# Generate a query file
echo "SELECT event_type, sum(revenue) FROM events GROUP BY event_type" > query.sql

# Run benchmark against local ClickHouse
clickhouse-benchmark -c 16 -i 1000 < query.sql
```

**Annotated Output:**
```text
Loaded 1 queries.
Queries executed: 1000.

localhost:9000, queries 1000, QPS: 843.232, R: 1.186 ms, E: 1.542 ms
# QPS: Queries per second (extremely high for a 1B row aggregation)
# R: Response time
0.000%          0.852 ms
99.000%         2.103 ms  # P99 Latency: 2ms across a billion rows!
99.900%         5.301 ms
```

---

## ⚔️ 5. Staff / Senior Interview Scenarios

**Q: Why should you NEVER use `UUID` as the first column in a ClickHouse `ORDER BY` key?**
**Staff Answer:** ClickHouse sorts data on disk by the `ORDER BY` key. UUIDs are completely random. Inserting random data destroys sequential disk writes, forces massive internal data reshuffling during background merges, destroys compression ratios (no delta encoding possible), and makes the sparse index useless. Always order by low-cardinality or monotonic columns first (e.g., `tenant_id`, `date`).

**Q: How do you handle real-time Updates/Deletes if ClickHouse is immutable?**
**Staff Answer:** Standard `ALTER TABLE ... UPDATE` is heavy and asynchronous (a "Mutation"). For real-time updates (like user balances), we use `ReplacingMergeTree` or `CollapsingMergeTree`. 
- **Mechanic:** You insert a new row with the same Primary Key but a higher timestamp. The engine automatically dedupes them in the background. 
- **Query Time:** We use `SELECT ... FINAL` to force on-the-fly deduplication, trading CPU time for real-time consistency.
