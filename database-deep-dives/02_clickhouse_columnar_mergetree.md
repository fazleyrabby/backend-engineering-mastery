# ClickHouse Columnar Storage & MergeTree Mechanics

> **Module:** Database Deep Dives (Topic 2.2)  
> **Source Mapping:** `backend-roadmap.md` (Level 29: #580–#585)

---

## 🏎️ Row-Oriented (MySQL) vs Column-Oriented (ClickHouse)

Imagine a table storing 100 Million analytics events (`id`, `user_id`, `event_name`, `created_at`):

```
MySQL (Row-Oriented on Disk):
[Row 1: 1, 100, 'click', '2026-08-01'] [Row 2: 2, 101, 'purchase', '2026-08-01'] ...

ClickHouse (Columnar Storage on Disk):
File 1 (id.bin):         [ 1, 2, 3, 4 ... 100000000 ]
File 2 (event_name.bin): ['click', 'purchase', 'view' ... ]
File 3 (created_at.bin): ['2026-08-01', '2026-08-01' ... ]
```

### Why ClickHouse is 100x Faster for Analytics:
If you run `SELECT COUNT(*), event_name FROM events WHERE created_at >= '2026-08-01' GROUP BY event_name`:
- **MySQL:** Must read ALL rows from disk (including unused columns like `id`, `user_id`), bottlenecked by Disk I/O.
- **ClickHouse:** Reads ONLY `created_at.bin` and `event_name.bin` files! Unused columns are never touched.
- **SIMD Vectorization:** CPU processes arrays of numbers in single instruction cycles.

---

## 🌲 The MergeTree Engine & Sparse Indexing

ClickHouse uses the **MergeTree** engine family. Data is written in **immutable Parts** on disk and merged periodically in the background.

```
ClickHouse Sparse Indexing (Granule = 8192 rows):
[Mark 0] ➔ Row #0 (Primary Key = '2026-08-01')
[Mark 1] ➔ Row #8192 (Primary Key = '2026-08-02')
[Mark 2] ➔ Row #16384 (Primary Key = '2026-08-03')
```

Unlike MySQL's dense B+Tree index (which indexes *every single row*), ClickHouse stores a **Sparse Index** (1 index entry per 8,192 rows). This allows the entire index for billions of rows to easily fit inside RAM!
