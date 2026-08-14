# Cloud Infrastructure: AWS Core (EC2, ECS, RDS, S3, VPC) & Kubernetes

> **Module:** Cloud & DevOps (Topic 5.3)  
> **Source Mapping:** `backend-roadmap.md` (Level 27: #546–#566) & `roadmap.md` (Tier 2: #286–#306)

---

## ☁️ 1. AWS Core Building Blocks for Backend Engineers

```
┌────────────────────────────── VPC (Virtual Private Cloud) ──────────────────────────────┐
│                                                                                        │
│  Public Subnet (Internet Gateway)                                                      │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐  │
│  │ [ Application Load Balancer (ALB) ]                                             │  │
│  └───────────────────────────┬──────────────────────────────────────────────────────┘  │
│                              │ Traffic Routed                                          │
│  Private Subnet (NAT Gateway)│                                                         │
│  ┌───────────────────────────▼──────────────────────────────────────────────────────┐  │
│  │ [ ECS / Fargate Container Tasks ]  or  [ EC2 Auto-Scaling Group ]               │  │
│  └───────────────────────────┬──────────────────────────────────────────────────────┘  │
│                              │ Internal DB Connection                                  │
│  Database Subnet (No Internet Access)                                                  │
│  ┌───────────────────────────▼──────────────────────────────────────────────────────┐  │
│  │ [ AWS RDS MySQL (Multi-AZ) ] ────── [ AWS ElastiCache Redis ]                   │  │
│  └──────────────────────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

- **VPC (Virtual Private Cloud):** Your isolated private cloud network.
- **Subnets:** Public (has internet gateway) vs Private (no direct internet access; DB lives here for security).
- **Security Groups:** State-full firewall rules restricting ports (e.g. RDS MySQL port 3306 accepts traffic ONLY from ECS security group).
- **ECS / Fargate:** Running Docker containers serverlessly without managing EC2 server nodes.
- **S3 (Simple Storage Service):** Infinite scale object storage for media uploads and backups.

---

## ☸️ 2. Kubernetes (k8s) Core Concepts

When scaling past single servers, Kubernetes orchestrates container clusters:

- **Pod:** The smallest deployable unit in K8s (wraps 1 or more Docker containers).
- **Deployment:** Declarative spec managing Pod replicas, rolling updates, and self-healing.
- **Service:** Load-balances traffic across dynamic Pod IP addresses.
- **Ingress:** Manages external HTTP/HTTPS routing into internal K8s Services.
