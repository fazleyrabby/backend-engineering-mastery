# ClickHouse Columnar Storage & MergeTree Mechanics

> **Module:** Database Deep Dives (Topic 2.2)  
> **Source Mapping:** `backend-roadmap.md` (Level 29: #580–#585)

---

## 💡 1. Conceptual Blueprint & First Principles

OLTP (Online Transaction Processing) databases like PostgreSQL use row-oriented storage optimized for point lookups and ACID compliance. 
OLAP (Online Analytical Processing) systems like ClickHouse use **Columnar Storage**. By storing data in columns, we achieve:
1. **Extreme Compression:** Similar data types compress beautifully (e.g., Run-Length Encoding).
2. **Reduced I/O:** Analytics queries usually aggregate a few columns across millions of rows. Columnar storage reads *only* the required column files from disk.
3. **Vectorized Execution:** Operating on arrays of data instead of single values maximizes CPU cache utilization and SIMD instructions.

## 🔬 2. Under-the-Hood Mechanics

### MergeTree Engine & Sparse Indexing

Data written to ClickHouse is grouped into immutable "Parts." Background threads continuously merge these parts (hence "MergeTree").

```mermaid
graph TD
    subgraph ["ClickHouse Sparse Indexing (Granule = 8192 rows)"]
        Index["primary.idx (Fits in RAM)"]
        Mark["Marks (.mrk file)"]
        Col["Data (.bin file) Compressed"]
        
        Index -- "Binary Search" --> Mark
        Mark -- "Byte Offset" --> Col
    end
```

Unlike B-Trees that index every row, a **Sparse Index** indexes one row per *Granule* (default 8,192 rows). 
- To find a date `2026-08-15`, ClickHouse binary-searches `primary.idx` to find the overlapping granules.
- It uses the `.mrk` files to find the exact byte offsets in the compressed `.bin` column files.
- It decompresses only those specific data blocks into RAM and scans the 8,192 rows.

## 💻 3. Production Code & Benchmarks

**ClickHouse MergeTree DDL:**
```sql
CREATE TABLE events (
    event_time DateTime,
    user_id UInt64,
    event_type String,
    revenue Float32
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(event_time)
ORDER BY (user_id, event_time)
SETTINGS index_granularity = 8192;
```

**Benchmark Context (1 Billion Rows):**
Query: `SELECT event_type, sum(revenue) FROM events WHERE event_time >= '2026-01-01' GROUP BY event_type;`
- **PostgreSQL:** ~45 seconds (Full table scan or heavy index bloat, large memory foot print).
- **ClickHouse:** ~0.2 seconds. ClickHouse reads *only* `event_time.bin`, `event_type.bin`, and `revenue.bin`. Vectorized processing aggregates chunks using SIMD instructions.

## ⚔️ 4. Staff / Senior Interview Scenarios

**Scenario 1:** *Why should you NEVER use `UUID` as the first column in a ClickHouse `ORDER BY` key?*
- **Staff Answer:** ClickHouse sorts data on disk by the `ORDER BY` key. UUIDs are highly random. Inserting random data destroys sequential disk writes, forces massive internal data reshuffling during background merges, and destroys compression ratios. Always order by low-cardinality or monotonic columns first (e.g., `tenant_id`, `timestamp`).

**Scenario 2:** *How do you handle updates and deletes in ClickHouse since it's immutable?*
- **Staff Answer:** ClickHouse is not designed for point updates. Standard `ALTER TABLE ... UPDATE` is a heavy, asynchronous mutation. For real-time updates, use engines like `ReplacingMergeTree` or `CollapsingMergeTree`. You insert a new row with a higher timestamp or a sign column, and the engine automatically collapses/deduplicates them during background merges. At query time, you write `SELECT ... FINAL` to force on-the-fly deduplication.
