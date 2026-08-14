# Database Scaling: Replication, Sharding & Partitioning

> **Module:** System Design & Real-Time (Topic 3.5)  
> **Source Mapping:** `backend-roadmap.md` (Level 23: #484–#487) & `roadmap.md` (Tier 1: #260–#263)

---

## 💡 1. Conceptual Blueprint & First Principles

Scaling relational databases requires addressing the physical limits of disk I/O, memory, and CPU on a single node.

- **Replication (Read Scaling):** Maintaining identical copies of data across multiple nodes. Solves read-heavy bottlenecks and provides High Availability (HA). Usually implemented as Primary-Replica.
- **Partitioning (Vertical/Horizontal on 1 DB):** Splitting large tables logically within the same physical machine. Reduces index size and speeds up localized queries.
- **Sharding (Write/Storage Scaling):** Distributing data across entirely separate database clusters. Crucial when the dataset size exceeds single-disk capacity or when write throughput saturates a single primary's I/O.

**The Golden Rule:** Never shard unless absolutely necessary. Sharding introduces massive operational complexity, ruins ACID transactions across shards, and complicates schema migrations.

---

## 🔬 2. Under-the-Hood Mechanics

### MySQL Binlog Replication
Replication relies on the Write-Ahead Log (WAL) or Binary Log (Binlog). 
- **Statement-Based:** Logs the exact SQL (`UPDATE users SET age = age + 1`). Faster, but non-deterministic functions like `NOW()` cause drift.
- **Row-Based:** Logs the exact row mutation. Heavier on network/disk, but guarantees absolute consistency. The industry standard.

### Sharding Architecture

```mermaid
graph TD
    A["Application (ProxySQL / Vitess)"]
    A -->|Hash(user_id) % 3 == 0| B["Shard 0 (Primary)"]
    A -->|Hash(user_id) % 3 == 1| C["Shard 1 (Primary)"]
    A -->|Hash(user_id) % 3 == 2| D["Shard 2 (Primary)"]
    B -.-> B_Rep["Shard 0 (Replica)"]
    C -.-> C_Rep["Shard 1 (Replica)"]
    D -.-> D_Rep["Shard 2 (Replica)"]
```

### Dealing with Replication Lag
Replication is fundamentally asynchronous. If a user writes data to the Primary and immediately reads from a Replica, the binlog might not have propagated, leading to stale data.
- **Solution:** Implement *Read-Your-Own-Writes* consistency. The application routes all reads for a specific user to the Primary for a short window (e.g., 5 seconds) after they perform a write, while routing all other read traffic to replicas.

---

## 💻 3. Production Code & Benchmarks

### ProxySQL Configuration for Read-Write Splitting

Instead of handling replication topologies in application code, use a Layer-7 database proxy like ProxySQL.

```sql
-- Define Hostgroups (0 = Primary, 1 = Replicas)
INSERT INTO mysql_servers(hostgroup_id, hostname, port) VALUES (0, '10.0.0.1', 3306);
INSERT INTO mysql_servers(hostgroup_id, hostname, port) VALUES (1, '10.0.0.2', 3306);
INSERT INTO mysql_servers(hostgroup_id, hostname, port) VALUES (1, '10.0.0.3', 3306);

-- Define Query Rules based on Regex
-- Send SELECTs to Hostgroup 1 (Replicas) unless it's a SELECT FOR UPDATE
INSERT INTO mysql_query_rules (rule_id, active, match_digest, destination_hostgroup, apply)
VALUES (1, 1, '^SELECT.*FOR UPDATE$', 0, 1);

INSERT INTO mysql_query_rules (rule_id, active, match_digest, destination_hostgroup, apply)
VALUES (2, 1, '^SELECT', 1, 1);

-- Send everything else (INSERT, UPDATE, DELETE) to Hostgroup 0 (Primary)
INSERT INTO mysql_query_rules (rule_id, active, match_digest, destination_hostgroup, apply)
VALUES (3, 1, '.*', 0, 1);

LOAD MYSQL QUERY RULES TO RUNTIME;
```

---

## ⚔️ 4. Staff / Senior Interview Scenarios

**Q: Your company has massively grown. You have 3 database shards (`hash(tenant_id) % 3`) but you are running out of disk space and need to migrate to 5 shards without downtime. How do you do it?**
> **A:** Changing the modulo algorithm instantly breaks data locality. We must use a **Consistent Hashing** ring, or better, an explicit **Directory-Based Sharding** mapping table. 
> To migrate without downtime, we follow a Multi-Phase Dual-Write approach:
> 1. Set up the new shards (4 and 5).
> 2. Implement dual-writes in the application (write to old shard and new shard simultaneously).
> 3. Run a background script to backfill historical data from old to new shards.
> 4. Verify data parity between old and new shards.
> 5. Switch read traffic to the new topology.
> 6. Stop dual-writes and decommission old routing logic.

**Q: How do you handle queries that require JOINs across different shards?**
> **A:** Cross-shard JOINs are computationally prohibitive. As an architect, I would mitigate this using:
> 1. **Denormalization:** Duplicate reference data across all shards so localized JOINs work.
> 2. **Application-Level Joins:** Query Shard A, extract IDs, then query Shard B using `IN (...)`, merging results in memory.
> 3. **Data Warehousing:** If the query is analytical, use CDC (Debezium) to stream all shard data into an OLAP database (Snowflake/ClickHouse) where massive JOINs can run freely.
