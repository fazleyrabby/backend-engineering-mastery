# Docker Mechanics: Namespaces, Cgroups & Reverse Proxies

> **Module:** Cloud & DevOps (Topic 5.1)  
> **Source Mapping:** `backend-roadmap.md` (Level 25: #511–#532) & `roadmap.md` (Tier 2: #271–#285)

---

## 🐋 1. How Docker Containers Actually Work Under the Hood

A Docker Container is **NOT a Virtual Machine**! There is no hypervisor running a separate OS kernel.

A container is just a normal Linux process restricted by 2 Linux Kernel features:

1. **Linux Namespaces (Isolation):**
   - `PID Namespace`: Container sees its process as PID 1.
   - `NET Namespace`: Dedicated virtual network interfaces & IP address.
   - `MNT Namespace`: Isolated filesystem mount points.
2. **Cgroups v2 (Control Groups - Resource Limits):**
   - Restricts CPU cores, RAM limits (`512MB`), and Disk I/O speed.

---

## 🔀 2. Nginx & Reverse Proxies

Nginx sits in front of backend servers handling SSL termination, static files, and load balancing:

```
Browser (HTTPS 443) ➔ [ Nginx (SSL Termination) ] ➔ [ PHP-FPM / Swoole (HTTP 8000) ]
```
