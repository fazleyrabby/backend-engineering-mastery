# Staff/Senior Python/FastAPI & Real-World Domain Interview Blueprint

> **Module:** Interview Practice (Topic 6.2)  
> **Source Mapping:** Experience at *Electronic First FZ LLE* & Portfolio Resume Analysis

---

## 🎯 Tailored Focus Areas Based on Your Actual Resume

Based on your 5+ years of experience at **Electronic First** and your portfolio:
- You built a **Configurable Fraud Detection Engine** (IP, Card, Velocity, BIN/ASN validation, Risk scoring).
- You integrated **Checkout.com, PayPal, Apple Pay, Google Pay, and Webhook-driven Dispute Sync**.
- You engineered **ClickHouse OLAP analytics dashboards** for customer behavior & gateway performance.
- You maintain a **Homelab with 41+ Docker containers, Traefik, Uvicorn/Gunicorn, and Cloudflare Tunnels**.

---

## 💳 1. Payment Infrastructure, Webhooks & Fraud Engine Interview Questions

### Q1: How did you design a Fraud Detection Engine that runs under 50ms without blocking checkout?
> **Answer Strategy:** Explain the pipeline:
> 1. Fast path checks (BIN/ASN lookup in Redis cache, IP risk score from local memory).
> 2. Asynchronous evaluation for heavy checks (velocity counters via Redis sliding window).
> 3. Weighted risk scoring algorithm (e.g. `Score > 80` ➔ Block, `Score 50-79` ➔ 3D Secure Verification, `Score < 50` ➔ Allow).

### Q2: PayPal or Checkout.com sends out-of-order webhooks (e.g. `PAYMENT.CAPTURED` arrives BEFORE `ORDER.CREATED`). How do you handle this?
> **Answer Strategy:**
> 1. Use the **Outbox / Deferred Queue Pattern**: If the target order row doesn't exist yet, push the webhook payload back onto a delayed queue (`delay(10)`) or store it in a `pending_webhooks` table.
> 2. Re-evaluate webhook processing after order creation completes.

### Q3: How do you handle Webhook Deduplication and Retries safely?
> **Answer Strategy:**
> - Store incoming `webhook_event_id` in DB with a `UNIQUE` index constraint inside a transaction.
> - Respond immediately with `200 OK` (within 200ms) to prevent provider timeouts and retry storms.
> - Hand off payload to a background Redis queue worker (`ProcessWebhookJob`).

---

## ⚡ 2. Python & FastAPI/Django Framework Deep Interview Scenarios

### Q4: Explain the internal differences between WSGI (Sync) and ASGI (Asyncio) application servers.
> **Answer Strategy:**
> - **WSGI (Gunicorn/Sync):** Spawns worker processes/threads that handle 1 request at a time, blocking on I/O operations (`synchronous`).
> - **ASGI (Uvicorn/FastAPI):** Boots the application once into RAM. Operates as an event-driven persistent app server (using an event loop and `epoll`). Non-blocking I/O allows handling thousands of concurrent requests (~5x-10x throughput boost for I/O bound tasks).

### Q5: What is the difference between Dependency Injection scopes: Transient, Singleton, and Request-Scoped (e.g., in FastAPI)?
> **Answer Strategy:**
> - **Transient**: Creates a new instance **every single time** the dependency is resolved.
> - **Singleton**: Creates one instance that persists across the **entire lifecycle of the application process**.
> - **Request-Scoped**: Creates one instance per **individual HTTP request** (crucial for state like Database Sessions so data doesn't leak between concurrent requests!).

### Q6: How does SQLAlchemy handle model relationships under the hood (`relationship`, `lazy='select'`, `lazy='joined'`)?
> **Answer Strategy:**
> - `lazy='select'`: Query Builder adds `WHERE foreign_key = primary_key` executing N+1 queries when accessed.
> - `lazy='joined'`: Uses an `LEFT OUTER JOIN` to fetch the related object in the same query.

---

## 📊 3. ClickHouse vs MySQL Analytics Integration

### Q7: Why did you choose ClickHouse instead of running analytics queries on MySQL Read Replicas?
> **Answer Strategy:**
> - MySQL Read Replicas still use **Row-Oriented InnoDB storage**. Calculating aggregate metrics (`SUM`, `COUNT`, `AVG`) over millions of rows forces MySQL to read full 16KB pages into the Buffer Pool, bottlenecked by Disk & RAM throughput.
> - **ClickHouse** uses **Column-Oriented MergeTree storage**. A query analyzing sales per country reads *only* the `country` and `amount` column files from disk, executing vector operations at **100x speed** with 90% LZ4 compression.

### Q8: How do you sync data from MySQL to ClickHouse in real-time?
> **Answer Strategy:**
> - **Option A (Batch Queue):** Celery Queue jobs batch insert chunks (`10,000 rows`) into ClickHouse every minute (ClickHouse hates single-row `INSERT` statements).
> - **Option B (CDC / Binlog):** Use Debezium / Kafka to stream MySQL binary logs into ClickHouse `ReplacingMergeTree` tables automatically.
