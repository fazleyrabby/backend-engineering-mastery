# Master Interview Blueprint: The 30 Core Senior Questions

> **Module:** Interview Practice (Topic 6.1)  
> **Source Mapping:** `roadmap.md` (Lines 465–570)

---

## 🎯 How to Use This Interview Practice Guide

In Staff & Senior Backend engineering interviews (and everyday engineering discussions), interviewers do not just want to hear *what* you built. They want to hear:
1. **What problem were you solving?**
2. **What technical requirements & constraints existed?**
3. **What data schema did you choose and why?**
4. **Where could race conditions or failures occur?**
5. **How did you make operations idempotent and safe to retry?**
6. **What trade-offs did you make?**

---

## ⚔️ The 30 Core Questions & Answer Strategies

### 1. Explain OOP and its four principles with examples from a Python application.
> **Answer Strategy:** Define Encapsulation (Private model attributes/getters), Abstraction (Hiding HTTP calls inside a Service class), Inheritance (Base BaseModel class), and Polymorphism (Injecting `PaymentGatewayInterface` with `Stripe` or `PayPal` implementations).

### 2. Explain SOLID and give an example where violating one creates a real problem.
> **Answer Strategy:** Mention OCP (Open/Closed) or DIP (Dependency Inversion). Example: A `PaymentController` with a massive `switch($provider)` statement. Adding a new provider forces modifying existing controller code, risking regressions on existing payment providers.

### 3. What problem does dependency injection solve?
> **Answer Strategy:** Removes hard-coded dependencies (`db = Database()`), enabling loose coupling, easier unit testing with mock objects (`mock.Mock(spec=PaymentGateway)`), and runtime flexibility.

### 4. What happens when a browser sends a request to a FastAPI/Django application?
> **Answer Strategy:** Trace ASGI/WSGI server ➔ App initialization ➔ Middleware stack ➔ Router ➔ Path Operation/View ➔ Dependency Injection resolution ➔ Response generation.

### 5. What happens when you enter https://example.com in a browser?
> **Answer Strategy:** DNS lookup (browser cache ➔ OS ➔ Resolver ➔ Authoritative DNS) ➔ TCP 3-way handshake (SYN, SYN-ACK, ACK) ➔ TLS 1.3 handshake ➔ HTTP GET request ➔ Server response ➔ DOM parsing & rendering.

### 6. Explain HTTP, TCP, DNS, and TLS at a high level.
> **Answer Strategy:** Use the Postal Analogy: DNS is the phonebook (domain ➔ IP), TCP is the tracking signature (reliable stream), TLS is the tamper-proof safe (encryption), HTTP is the letter content.

### 7. How would you design an orders database?
> **Answer Strategy:** Define `orders` (id, user_id, status, total_cents, created_at) and `order_items` (id, order_id, product_id, price_cents, quantity). Mention indexing `(user_id, created_at)` and foreign key constraints for referential integrity.

### 8. How do database indexes work?
> **Answer Strategy:** Explain B+ Tree data structures. Internal nodes act as pointers in RAM; leaf nodes contain actual pointers/rows on disk. Reduces search from $O(N)$ full table scan to $O(\log N)$.

### 9. How would you investigate a slow MySQL query?
> **Answer Strategy:** Check MySQL Slow Query Log ➔ Run `EXPLAIN ANALYZE` ➔ Check for missing indexes (type `ALL` / full table scan) ➔ Check for high disk temporary tables (`Using temporary; Using filesort`) ➔ Add composite index or optimize query structure.

### 10. What does EXPLAIN tell you?
> **Answer Strategy:** Key fields: `type` (`const` > `ref` > `range` > `ALL`), `possible_keys`, `key` (index used), `rows` (estimated rows examined), `Extra` (`Using index` = covering index, `Using filesort` = unindexed sort).

### 11. Explain ACID.
> **Answer Strategy:** Atomicity (All or nothing), Consistency (Valid database constraints), Isolation (Transactions don't interfere with each other via MVCC), Durability (Committed data survives power failure via WAL/Redo log).

### 12. Explain database isolation levels.
> **Answer Strategy:** Read Uncommitted (Dirty reads), Read Committed (Non-repeatable reads), Repeatable Read (Default InnoDB; Gap locks prevent phantom reads), Serializable (Strict locking).

### 13. Give a real-world example of a race condition.
> **Answer Strategy:** E-commerce stock subtraction: 2 users read `stock = 1` simultaneously. Both check `stock > 0` (true) and both write `stock - 1 = 0`. Stock becomes -1 (oversold).

### 14. Two users try to buy the last product simultaneously. How do you prevent overselling?
> **Answer Strategy:**
> - Option A (Database Atomic Query): `UPDATE products SET stock = stock - 1 WHERE id = 1 AND stock > 0;` Check affected rows.
> - Option B (Pessimistic Locking): `SELECT * FROM products WHERE id = 1 FOR UPDATE;`
> - Option C (Redis Lock): `SET lock:product:1 UUID NX PX 5000`.

### 15. What is a database deadlock?
> **Answer Strategy:** Transaction 1 locks Row A and requests Row B. Transaction 2 locks Row B and requests Row A. Both wait forever. InnoDB detects this graph cycle and rolls back 1 transaction (`InnoDB: Deadlock found`).

### 16. Optimistic vs pessimistic locking?
> **Answer Strategy:**
> - **Pessimistic:** `SELECT FOR UPDATE` locks the row immediately. Best for high contention / financial updates.
> - **Optimistic:** Includes a `version` or `updated_at` column. `UPDATE ... WHERE id = 1 AND version = 5`. Best for low contention / read-heavy apps.

### 17. What is idempotency?
> **Answer Strategy:** An operation that produces the same result regardless of how many times it is executed (e.g. `SET score = 100` vs non-idempotent `INCR score`).

### 18. Design an idempotent payment endpoint.
> **Answer Strategy:** Client sends `Idempotency-Key` header. Server attempts atomic `SET lock:key UUID NX PX 30000` in Redis. Stores final response payload in Redis/DB associated with the key. Subsequent retries return the cached response.

### 19. A payment provider sends the same webhook three times. How do you process it safely?
> **Answer Strategy:** Store processed `webhook_event_id` in a database table with a `UNIQUE` constraint. Wrap event processing in a transaction. If insert fails on duplicate key error, return `200 OK` without re-processing.

### 20. The payment succeeds but your application crashes before recording it. How do you recover?
> **Answer Strategy:** Implement a background **Reconciliation Job** (Cron) that queries the payment provider's API for recent successful transactions and matches them against local DB records.

### 21. How would you design a reliable third-party API integration?
> **Answer Strategy:** Set explicit connection/response timeouts ➔ Implement exponential backoff with jitter ➔ Wrap calls in a Circuit Breaker pattern ➔ Store outgoing requests in an Outbox queue table.

### 22. Why use queues?
> **Answer Strategy:** Decouples heavy I/O operations (sending emails, processing images) from the HTTP request/response cycle, dropping API response times from 3s to 20ms.

### 23. How do you make a queue job safe to retry?
> **Answer Strategy:** Ensure job handlers are idempotent (e.g. check if `order.status == 'paid'` before sending receipt email).

### 24. What is the difference between a cache and a database?
> **Answer Strategy:** Cache (Redis) stores transient data in RAM for sub-millisecond reads. Database (MySQL) stores persistent data on Disk with ACID guarantees.

### 25. What causes cache invalidation problems?
> **Answer Strategy:** Stale data when DB is updated without invalidating cache keys, race conditions between concurrent writes, or lack of proper cache key versioning (`users:1:v2`).

### 26. How would you design a Python application that handles 10x today's traffic?
> **Answer Strategy:** Make application stateless ➔ Scale worker nodes horizontally behind Nginx load balancer ➔ Introduce Redis caching (Cache-Aside) ➔ Add Read Replicas for MySQL ➔ Offload heavy work to Celery queue workers.

### 27. Monolith vs microservices — when would you choose each?
> **Answer Strategy:** Choose Monolith/Modular Monolith for small-to-medium teams for low operational complexity and single DB ACID transactions. Choose Microservices when scaling engineering team size (>50-100 devs) across independent domain boundaries.

### 28. How would you design a payment/reconciliation system?
> **Answer Strategy:** Use Immutable Financial Ledgers (Double-Entry Bookkeeping: Debits = Credits) ➔ Store money as integer minor units ➔ Run daily automated reconciliation matching internal ledger logs against bank/Stripe settlement CSVs.

### 29. How would you investigate a production incident where response times suddenly increased?
> **Answer Strategy:** Check APM / Prometheus metrics (CPU, RAM, DB connection pool, HTTP latency) ➔ Check MySQL slow query log ➔ Check error tracking (Sentry) for unhandled exception spikes ➔ Inspect Nginx access logs for traffic spikes or DDoS.

### 30. Explain a complex system you built end-to-end.
> **Answer Strategy:** Walk through: Problem ➔ Functional/Non-functional Requirements ➔ Architecture Diagram ➔ DB Schema Choice ➔ Failure Cases & Idempotency Handles ➔ Monitoring & Trade-offs.
