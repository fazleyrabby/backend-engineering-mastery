# Microservices vs Monolith, Saga Pattern & Observability

> **Module:** System Design & Real-Time (Topic 3.6)  
> **Source Mapping:** `backend-roadmap.md` (Level 22 & 28) & `roadmap.md` (Tier 1: #264–#270)

---

## 🏛️ 1. Monolith vs. Modular Monolith vs. Microservices

| Pattern | Architectural Characteristics | When to Choose |
| :--- | :--- | :--- |
| **Monolith** | Single codebase, single deployment unit, shared database. | Early-stage startups, fast iteration, small teams (<15 engineers). |
| **Modular Monolith** | Single codebase, but strictly decoupled domain modules with zero cross-module DB calls. | **Recommended for most growing companies.** Clean boundaries without microservice complexity. |
| **Microservices** | Independent codebases, independent deployments, **Database-per-Service**. | Large engineering organizations (hundreds of developers) with distinct domain teams. |

---

## 🔄 2. Distributed Transactions: The Saga Pattern

In microservices with **Database-per-Service**, traditional ACID SQL transactions spanning multiple databases are impossible!

We use the **Saga Pattern** (a sequence of local transactions with **Compensating Actions**):

```
Order Service               Payment Service             Inventory Service
      │                            │                            │
      ├─── 1. Create Pending Order ┼───────────────────────────►│
      │                            ├─── 2. Charge Card ────────►│
      │                            │    (Payment Fails!)       │
      │◄── 3. Trigger Compensation ┴───────────────────────────┤
      │    (Cancel Order & Release Inventory)                  │
```

---

## 📊 3. The 3 Pillars of Observability

1. **Metrics (Prometheus / Grafana):** Aggregated numerical data over time (CPU usage, requests per second, HTTP 500 error rates).
2. **Logs (Loki / ELK Stack):** Timestamped event records (`User 100 failed payment: Timeout`).
3. **Distributed Tracing (Jaeger / OpenTelemetry):** Tracking a single request's path (`Trace ID: abc-123`) as it hops across 10 microservices to pinpoint latency bottlenecks.
