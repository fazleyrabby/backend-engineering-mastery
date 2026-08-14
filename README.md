# 🚀 Staff Backend Engineering Architecture & Mastery Repository

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-InnoDB-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Redis](https://img.shields.io/badge/Redis-PubSub%2FRedlock-DC382D?style=flat-square&logo=redis&logoColor=white)](https://redis.io)
[![AWS](https://img.shields.io/badge/AWS-SAA--C03-232F3E?style=flat-square&logo=amazon-aws&logoColor=white)](https://aws.amazon.com)
[![Status](https://img.shields.io/badge/Status-Production--Ready-4ade80?style=flat-square)](#-master-syllabus-overview)

A comprehensive, production-grade engineering mastery repository and reference blueprint for **Senior/Staff Backend Engineers** and **AWS Certified Solutions Architects (SAA-C03)**.

Covering low-level OS kernel mechanics, database storage engines (MySQL InnoDB, ClickHouse MergeTree, Redis SDS), distributed system design (WebSockets, Kafka, Saga pattern, Idempotent Ledgers), Laravel Octane internals, and cloud infrastructure.

---

## 📚 Master Syllabus Overview

| Module | Core Domain | Key Engineering Topics Covered | Resource Link |
| :--- | :--- | :--- | :---: |
| **01** | **CS Fundamentals** | CPU Registers, Virtual Memory, Linux `epoll`, TCP/TLS 1.3, Big-O, OWASP Security | [`cs-fundamentals/`](cs-fundamentals/) |
| **02** | **Database Storage Engines** | MySQL InnoDB Buffer Pool & MVCC, ClickHouse Columnar MergeTrees, Redis SDS & Redlock | [`database-deep-dives/`](database-deep-dives/) |
| **03** | **System Design & Real-Time** | WebSockets (100k sockets), Financial Idempotency, Kafka streams, Sharding, Saga | [`system-design/`](system-design/) |
| **04** | **Laravel Internals** | Reflection Container, Request Lifecycle, Eloquent Hydration, Octane Persistent Safety | [`laravel-internals/`](laravel-internals/) |
| **05** | **Cloud DevOps & AWS SAA-C03** | Docker Namespaces, CI/CD, AWS VPC/Subnets/RDS, Terraform IaC, AWS SAA-C03 Guide | [`cloud-devops/`](cloud-devops/) |
| **06** | **Interview Practice** | 30 Core Senior Architectural Scenarios & Domain Deep Dives (Fraud Engine, Webhooks) | [`interview-practice/`](interview-practice/) |

---

## 🧪 Interactive Benchmark Scripts

The repository includes CLI performance benchmark scripts under `sample-codes/`:
- `sample-codes/01_opcode_test.php` — Zend Virtual Machine Opcode inspection.
- `sample-codes/02_eloquent_vs_db_benchmark.php` — Memory footprint & CPU benchmark comparing Eloquent hydration vs PDO raw arrays.
- `sample-codes/03_mutex_race_condition_test.php` — In-memory race conditions vs Mutex locking.

---

## 🔴 The 30 Senior Architectural Scenarios Matrix

This repository includes concrete answer strategies and system design breakdowns for 30 critical senior interview questions, including:

- **Overselling Prevention:** Mutex locks, Redis ZSET sliding windows, and pessimistic `SELECT FOR UPDATE`.
- **Financial Ledgers:** Double-entry immutable accounting, integer minor units (cents), and reconciliation.
- **Out-of-Order Webhooks:** Transactional Outbox Pattern, idempotency keys, and exponential backoff retry.
- **High Concurrency:** Scaling WebSockets to 100k open sockets with Soketi/Reverb and Redis Pub/Sub relay.

Detailed answers available in [`interview-practice/01_master_30_interview_questions.md`](interview-practice/01_master_30_interview_questions.md) and [`STUDY_GUIDE.md`](STUDY_GUIDE.md).

---

## 👤 Author & License

- **Author:** Md. Fazley Rabbi (Senior Backend Engineer & Staff Architect Target)
- **License:** MIT License
