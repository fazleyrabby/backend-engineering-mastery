# Database Scaling: Replication, Sharding & Partitioning

> **Module:** System Design & Real-Time (Topic 3.5)  
> **Source Mapping:** `backend-roadmap.md` (Level 23: #484–#487) & `roadmap.md` (Tier 1: #260–#263)

---

## 🗄️ 1. Database Replication (Primary-Replica)

To handle heavy read traffic (`90% Reads, 10% Writes`):

```
                       ┌──► [ Read Replica 1 ] (MySQL Slave)
[ App Workers ] ───────┼──► [ Read Replica 2 ] (MySQL Slave)
  │ (Writes ONLY)      └──► [ Read Replica 3 ] (MySQL Slave)
  ▼
[ Primary Database ] ──Replicates Binlog asynchronously──►
```

### Replication Lag Penalty:
If User A updates their profile (writes to Primary) and immediately refreshes the page (reads from Replica before replication catches up), User A sees old data!  
*Fix:* **Sticky sessions / Read-Your-Own-Writes:** Read from Primary for 5 seconds after a write operation.

---

## 🧩 2. Sharding vs. Partitioning

- **Partitioning (Vertical/Horizontal on 1 DB):** Splitting a giant table into smaller sub-tables inside the *same database instance* (e.g., `orders_2026_08`).
- **Sharding (Horizontal across multiple DB servers):** Splitting data across *entirely separate database servers* using a **Shard Key**:

```
Shard Key: Hash(user_id) % 3

user_id = 101 ➔ Hash = 1 ➔ Shard Server 1
user_id = 102 ➔ Hash = 2 ➔ Shard Server 2
user_id = 103 ➔ Hash = 0 ➔ Shard Server 3
```

### Trade-offs of Sharding:
1. **No Cross-Shard JOINs:** SQL `JOIN` across different physical database servers is impossible without expensive application-level merging.
2. **Consistent Hashing:** Adding Shard Server #4 requires rebalancing keys using **Consistent Hashing** to avoid migrating 100% of the dataset.
