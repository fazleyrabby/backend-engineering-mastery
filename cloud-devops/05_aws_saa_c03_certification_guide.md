# AWS Certified Solutions Architect - Associate (SAA-C03) Exam Guide

> **Module:** Cloud & DevOps (Topic 5.5)  
> **Target:** AWS SAA-C03 Certification & High-Availability Cloud System Design

---

## 🎯 The 4 SAA-C03 Exam Domains

```
┌────────────────────────────────────────────────────────────────────────┐
│            AWS SAA-C03 Solution Architect Exam Domains                 │
├─────────────────────────────────────────────────────────┬──────────────┤
│ Domain 1: Design Secure Architectures                   │ 30% Weight   │
│ Domain 2: Design Resilient Architectures                │ 26% Weight   │
│ Domain 3: Design High-Performing Architectures          │ 24% Weight   │
│ Domain 4: Design Cost-Optimized Architectures           │ 20% Weight   │
└─────────────────────────────────────────────────────────┴──────────────┘
```

---

## 🔐 Domain 1: Design Secure Architectures (30%)

### 1. IAM (Identity & Access Management)
- **Least Privilege Principle:** Never grant `*` permissions. Use IAM Roles for EC2/ECS tasks instead of hardcoding secret keys inside code!
- **IAM Policies:** JSON documents defining `Effect` (Allow/Deny), `Action`, `Resource`, and `Condition`.

### 2. Networking Security (VPC, Security Groups & NACLs)
- **Security Groups (Stateful):** If inbound traffic is allowed on port 443, outbound response is automatically allowed. Applied at **Instance Level**.
- **Network ACLs (NACLs - Stateless):** Evaluated in rule number order. Must explicitly allow BOTH inbound and outbound traffic. Applied at **Subnet Level**.

---

## 🏛️ Domain 2: Design Resilient Architectures (26%)

### 1. High Availability (HA) & Disaster Recovery (DR)
- **Multi-AZ Deployment:** Deploying RDS/EC2 across multiple Availability Zones with automatic failover.
- **RTO (Recovery Time Objective):** Maximum acceptable downtime.
- **RPO (Recovery Point Objective):** Maximum acceptable data loss.

### 2. Auto Scaling & Load Balancing
- **Application Load Balancer (ALB):** Layer 7 (HTTP/HTTPS) routing, path-based routing (`/api` vs `/static`).
- **Network Load Balancer (NLB):** Layer 4 (TCP/UDP) ultra low-latency handling millions of requests per second.

---

## ⚡ Domain 3: Design High-Performing Architectures (24%)

### 1. Storage Performance
- **S3 Storage Classes:** Standard ➔ Standard-IA (Infrequent Access) ➔ Glacier Instant Retrieval ➔ Glacier Flexible ➔ Deep Archive.
- **EBS Volume Types:**
  - `gp3` / `gp2`: General purpose SSD (boot volumes).
  - `io2` / `io1`: Provisioned IOPS SSD for high-performance databases.
- **EFS (Elastic File System):** Network File System (NFS) accessible concurrently across hundreds of EC2 instances.

---

## 💰 Domain 4: Design Cost-Optimized Architectures (20%)

### 1. EC2 Pricing Models
- **On-Demand:** Pay by the second. No long-term commitment (unpredictable workloads).
- **Reserved Instances (RI) / Savings Plans:** 1 or 3-year commitment (up to **72% discount** for steady-state workloads).
- **Spot Instances:** Bid on spare AWS capacity (up to **90% discount**). *Catch:* AWS can terminate with a 2-minute notice! Ideal for stateless queue workers or batch processing.
