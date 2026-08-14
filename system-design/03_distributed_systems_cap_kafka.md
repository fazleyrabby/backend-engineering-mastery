# Distributed Systems, CAP Theorem & Event-Driven Architecture

> **Module:** System Design & Real-Time (Topic 3.3)  
> **Source Mapping:** `backend-roadmap.md` (Level 23 & 29: #588–#593) & `roadmap.md` (Tier 3: #322–#326)

---

## 🔺 1. CAP Theorem & PACELC Theorem

In a distributed database network with multiple nodes, you can only pick **2 out of 3** guarantees during a network partition:

```
                  Consistency (C)
                     /\
                    /  \
                   /    \
                  /  AP  \
                 /________\
Availability (A)            Partition Tolerance (P)
```

- **CP (Consistency + Partition Tolerance):** If network partition occurs, refuse writes to avoid stale data (e.g., Banking Ledgers, MySQL Primary-Replica with strict synchronous replication).
- **AP (Availability + Partition Tolerance):** Accept writes on available nodes even if they haven't synced yet (Eventual Consistency, e.g., Cassandra, DynamoDB, DNS).

### PACELC Theorem (Extending CAP):
If there is a Partition (**P**), trade off Availability (**A**) vs Consistency (**C**); **E**lse, trade off Latency (**L**) vs Consistency (**C**).

---

## 📥 2. Apache Kafka & Message Queue Architecture

Kafka is a **Distributed Commit Log** designed for multi-gigabyte event streaming:

```
[ Producer (Web App) ] ──Writes──► [ Topic: order-events ]
                                       ├── Partition 0 ──► [ Consumer Group A - Worker 1 ]
                                       └── Partition 1 ──► [ Consumer Group A - Worker 2 ]
```

### Key Differences: Message Queue (RabbitMQ) vs Event Stream (Kafka)
- **RabbitMQ (AMQP):** Messages are deleted from the queue once acknowledged by a worker (Push Model). Great for task processing.
- **Kafka:** Events are immutable and retained on disk for days/months (Pull Model). Multiple independent consumer groups can replay events from any offset.
