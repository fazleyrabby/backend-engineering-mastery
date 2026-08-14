# Redis Internals, Data Structures & Distributed Locks

> **Module:** Database Deep Dives (Topic 2.3)  
> **Source Mapping:** `backend-roadmap.md` (Level 13: #304–#321) & `roadmap.md` (Tier 1: #174–#188)

---

## ⚡ 1. Redis Data Structures Under the Hood

| Redis Data Type | C Data Structure | Time Complexity | Real-World Backend Scenario |
| :--- | :--- | :--- | :--- |
| **String** | SDS (Simple Dynamic String) | $O(1)$ | Session storage, counters (`INCR`). |
| **Hash** | Ziplist / Hashtable | $O(1)$ | User profile caching (`HSET user:1 name "Fazley"`). |
| **List** | Quicklist (Doubly-linked list of ziplists) | $O(1)$ push/pop | Simple background queues (`LPUSH` / `RPOP`). |
| **Set** | Intset / Hashtable | $O(1)$ | Unique IP tracking, tag matching. |
| **Sorted Set (ZSET)** | **SkipList + Hashtable** | $O(\log N)$ | Leaderboards, **Sliding Window Rate Limiters**. |

---

## 🔒 2. Distributed Locking with Redis (Redlock & Lua Scripts)

When running multiple web workers, standard in-memory locks don't work across machines. We use Redis for **Distributed Locks**.

### The Safe Release Lua Script Pattern
To acquire a lock: `SET lock_key "unique_uuid_123" NX PX 30000` (Set if Not Exists, Expire in 30s).

To release a lock safely (preventing Worker A from releasing Worker B's lock if A timed out):
```lua
-- Lua script executed atomically in Redis:
if redis.call("get", KEYS[1]) == ARGV[1] then
    return redis.call("del", KEYS[1])
else
    return 0
end
```
