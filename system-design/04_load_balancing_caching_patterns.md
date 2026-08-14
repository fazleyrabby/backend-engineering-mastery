# System Design Blueprint: Load Balancing, Caching & Scaling

> **Module:** System Design & Real-Time (Topic 3.4)  
> **Source Mapping:** `backend-roadmap.md` (Level 23: #465–#494) & `roadmap.md` (Tier 1: #247–#270)

---

## ⚖️ 1. Horizontal vs. Vertical Scaling & Stateless Applications

- **Vertical Scaling (Scale Up):** Adding more CPU/RAM to 1 machine. *Limitation:* Physical hardware ceiling & single point of failure (SPOF).
- **Horizontal Scaling (Scale Out):** Adding more server instances behind a **Load Balancer**.

```
                           ┌──► App Server 1 (Stateless) ──┐
[ Users ] ──► [ Load Balancer ] ┼──► App Server 2 (Stateless) ──┼──► [ Central Redis / MySQL ]
                           └──► App Server 3 (Stateless) ──┘
```

### Rule of Stateless Applications:
App servers MUST NOT store session state or uploaded files on local disks. Sessions belong in **Redis**, files belong in **AWS S3 / Object Storage**.

---

## 🏎️ 2. Caching Strategies & Cache Invalidation Patterns

### Cache Strategies:
1. **Cache-Aside (Lazy Loading):** App reads from Cache first. On cache miss, reads from DB and populates Cache.
2. **Write-Through:** App writes to Cache, and Cache synchronously writes to DB.
3. **Write-Behind (Write-Back):** App writes to Cache, and Cache asynchronously flushes writes to DB in batches.

### The 3 Classic Cache Breakdown Disasters:
- **Cache Stampede (Thundering Herd):** A popular cache key expires (`homepage_top_products`). 10,000 concurrent requests hit DB at the exact same millisecond. *Fix:* Mutex locks / Probabilistic Early Expiration (XFetch).
- **Cache Penetration:** Requests for non-existent keys (e.g. `user_id = -999`) bypass cache and hit DB continuously. *Fix:* Bloom Filters or caching `NULL` values.
- **Cache Avalanche:** Thousands of cache keys expire at the exact same second. *Fix:* Add random jitter to TTL (`TTL = 3600 + rand(0, 300)`).
