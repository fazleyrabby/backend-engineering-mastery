# Advanced Algorithms, Graph Patterns & Dynamic Programming

> **Module:** CS Fundamentals (Topic 1.6)  
> **Source Mapping:** `backend-roadmap.md` (Level 3: #73–#95) & `roadmap.md` (Tier 1: Data Structures & Algorithms)

---

## 🧮 1. Essential Algorithm Patterns for Backend Engineers

| Algorithm Pattern | Problem Types | Core Mechanic | Time / Space Complexity | Backend Application |
| :--- | :--- | :--- | :--- | :--- |
| **Two Pointers** | Sorted Arrays, Pair Matching, Container Water | 2 pointers moving towards/away from each other. | $O(N)$ Time, $O(1)$ Space | Log filtering, fast range matching. |
| **Sliding Window** | Subarray sums, String matching, Rate limiting | Maintain a dynamic range window over contiguous data. | $O(N)$ Time, $O(1)$ Space | **Redis Sliding Window Rate Limiting**, Traffic Throttling. |
| **Prefix Sum** | Range sum queries (`SUM(val) BETWEEN i AND j`) | Pre-calculate cumulative sum array `P[i] = P[i-1] + A[i]`. | $O(1)$ Query Time after $O(N)$ prep | Financial reporting, real-time balance calculations. |
| **Binary Search** | Logarithmic search in sorted space | Repeatedly divide search interval in half. | $O(\log N)$ Time | **Database Sparse Index Lookup** (ClickHouse / B+Trees). |

---

## 🌐 2. Graph Algorithms & Breadth-First Search (BFS) / Depth-First Search (DFS)

Graphs represent networks of interconnected nodes (e.g., social connections, service dependencies, database foreign key constraints).

```
Graph Representation:
Node A ──► Node B ──► Node C
  │                     ▲
  └─────────────────────┘
```

### BFS (Breadth-First Search - Queue Based)
- Explores neighbor nodes layer-by-layer (uses a **Queue $O(V+E)$**).
- **Use Case:** Finding the **Shortest Path** (e.g., fewest hops between microservices or social network connections).

### DFS (Depth-First Search - Stack/Recursion Based)
- Explores as deep as possible down each branch before backtracking (uses a **Stack / Recursion $O(V+E)$**).
- **Use Case:** **Cycle Detection** (e.g., detecting circular dependency deadlocks in job processing workflows).

---

## 🧩 3. Dynamic Programming (DP) & Memoization

Dynamic Programming breaks down complex problems into overlapping subproblems and stores intermediate results (**Memoization**) so work is never repeated.

### The Memoization Blueprint:
```php
// Fibonacci with Memoization (O(N) instead of exponential O(2^N))
function fibonacci(int $n, array &$memo = []): int {
    if ($n <= 1) return $n;
    if (isset($memo[$n])) return $memo[$n];
    
    $memo[$n] = fibonacci($n - 1, $memo) + fibonacci($n - 2, $memo);
    return $memo[$n];
}
```
**Backend Application:** Caching expensive intermediate computation steps (e.g., discount rule combinations or route distance matrix computations).
