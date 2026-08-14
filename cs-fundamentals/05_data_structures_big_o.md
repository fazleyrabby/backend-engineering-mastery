# Core Data Structures & Time Complexity (Big-O)

> **Module:** CS Fundamentals (Topic 1.4)  
> **Source Mapping:** `backend-roadmap.md` (Level 3: #73–#95) & `roadmap.md` (Tier 1: #18, #19)

---

## ⏱️ 1. Big-O Cheat Sheet for Backend Engineers

| Data Structure | Access (Lookup) | Search | Insertion | Deletion | Primary Real-World Backend Use Case |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Array / Slice** | $O(1)$ | $O(N)$ | $O(N)$ (or $O(1)$ at end) | $O(N)$ | Contiguous buffer memory, iteration. |
| **Hash Table / Map** | $O(1)$ | $O(1)$ | $O(1)$ | $O(1)$ | Redis Caches, In-memory lookups, PHP arrays. |
| **B+ Tree Index** | $O(\log N)$ | $O(\log N)$ | $O(\log N)$ | $O(\log N)$ | **MySQL InnoDB Database Indexes**. |
| **Skip List** | $O(\log N)$ | $O(\log N)$ | $O(\log N)$ | $O(\log N)$ | **Redis Sorted Sets (ZSET)**. |
| **Linked List** | $O(N)$ | $O(N)$ | $O(1)$ | $O(1)$ | Queues, LRU Cache eviction chains. |

---

## 🔑 2. How Hash Maps Work Under the Hood

A **Hash Map** converts any key (e.g., string `"user:100"`) into an array index using a **Hash Function**:

```
Key: "user:100" ➔ Hash Function ➔ Hash Code: 8492049 ➔ Index (Hash % ArraySize) ➔ Slot #4
```

### Hash Collisions
When two keys hash to the same array index (e.g., Key A and Key B both land on Slot #4):
1. **Chaining (Used in Java/PHP):** Slot #4 contains a Linked List or Red-Black Tree storing `[Key A -> Val A] -> [Key B -> Val B]`.
2. **Open Addressing:** Search for the next available empty slot in the array.

*Interview Warning:* If a malicious user sends keys that all hash to the exact same slot, lookups degrade from $O(1)$ to **$O(N)$ (Hash DoS Attack)**!

---

## 🌲 3. Why Databases Use B+ Trees Instead of Binary Trees

Binary Search Trees (BST) have 2 children per node. **B+ Trees** have high fan-out (hundreds of children per node):

```
                       [ 20 | 50 | 80 ]   <-- Internal Index Node (In RAM Buffer Pool)
                      /     |    |     \
     [ 5 | 10 | 15 ] ─── [ 25 | 30 ] ─── [ 55 | 70 ] ─── [ 85 | 90 ]  <-- Leaf Nodes (On Disk)
```

1. **Low Tree Height:** A 3-level B+ Tree can store millions of rows. Finding any row requires only **3 disk reads**!
2. **Leaf Node Linked List:** All actual data row pointers reside in leaf nodes connected by a doubly-linked list, making **RANGE queries (`WHERE age BETWEEN 20 AND 50`) lightning fast**!
