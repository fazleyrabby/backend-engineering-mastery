# Staff Engineering Leadership, System Trade-offs & High-Scale Systems

> **Module:** System Design & Real-Time (Topic 3.7)  
> **Source Mapping:** Staff / Lead Engineer Interview Preparation & Career Growth

---

## 🏛️ 1. What Distinguishes a Senior Engineer from a Staff/Principal Architect?

| Metric | Senior Software Engineer | Staff / Principal Engineer |
| :--- | :--- | :--- |
| **Scope of Impact** | Delivers complex features / services within a domain team. | Drives architecture across multiple teams, systems, and organization-wide infrastructure. |
| **Problem Formulation** | Receives technical requirements and builds robust solutions. | Identifies unstated business/technical problems, defines RFCs, and sets engineering standards. |
| **Trade-off Philosophy** | Optimizes for clean code, speed, and unit test coverage. | Optimizes for long-term maintainability, operational cost, fault tolerance, and organizational velocity. |
| **Communication** | Teaches mid/junior devs syntax and framework patterns. | Translates complex technical trade-offs to C-level executives and mentors senior engineers. |

---

## ⚖️ 2. Architectural Decision Records (ADR) & System RFCs

When proposing major system changes (e.g. migrating analytics from MySQL to ClickHouse, introducing Octane/Swoole, or breaking out a microservice):

### The Standard ADR Format:
1. **Title & Status:** Proposed / Accepted / Superseded.
2. **Context:** What business/technical bottleneck are we facing? (e.g. "MySQL dashboard queries take 12 seconds during flash sales").
3. **Decision:** "We will adopt ClickHouse as an OLAP store for analytical event logs, using batch queue insertion."
4. **Consequences & Trade-offs:**
   - *Positive:* Dashboard load times drop from 12s to 40ms.
   - *Negative:* Adds operational overhead of managing ClickHouse nodes and maintaining ETL pipelines.

---

## 💥 3. Incident Response & Root Cause Analysis (RCA / Post-Mortems)

During a major production outage (e.g. Redis connection pool exhaustion causing 504 Gateway Timeouts):

```
Outage Detection (Prometheus Alert) ➔ Mitigate First (Scale Nodes / Rollback) ➔ Root Cause Analysis (RCA) ➔ Blameless Post-Mortem
```

### The "5 Whys" Method:
1. *Why did API servers crash?* ➔ Redis ran out of memory.
2. *Why did Redis run out of memory?* ➔ A single key `active_users` grew to 6GB.
3. *Why did `active_users` grow to 6GB?* ➔ An unbounded array was appended on every request without TTL.
4. *Why wasn't TTL enforced?* ➔ Code review missed the missing expiration parameter.
5. *Why did code review miss it?* ➔ No automated static analysis rule flagged unbounded Redis writes.  
**Action Item:** Implement static analysis check + Redis maxmemory policy.
