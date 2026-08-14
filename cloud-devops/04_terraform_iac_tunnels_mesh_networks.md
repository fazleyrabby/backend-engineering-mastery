# Infrastructure as Code (Terraform), Reverse Proxies & Cloud Native Homelabs

> **Module:** Cloud & DevOps (Topic 5.4)  
> **Source Mapping:** `backend-roadmap.md` (Level 25 & 27) & Homelab Stack (Traefik, Cloudflare Tunnels, Tailscale)

---

## 🛠️ 1. Infrastructure as Code (IaC) with Terraform

Instead of manually clicking through the AWS Console, **Terraform** declares cloud infrastructure as version-controlled code.

```hcl
# main.tf — Declarative AWS Infrastructure
resource "aws_vpc" "main" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_hostnames = true

  tags = {
    Name = "production-vpc"
  }
}

resource "aws_subnet" "public_1" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.1.0/24"
  map_public_ip_on_launch = true
}
```

### The Terraform Execution Lifecycle:
1. `terraform init`: Downloads provider plugins (AWS, Cloudflare).
2. `terraform plan`: Previews exact changes (Add, Modify, Destroy) without touching infrastructure.
3. `terraform apply`: Executes API calls against AWS to match desired state.
4. `terraform.tfstate`: State file tracking real-world cloud resource IDs (*Must be stored in remote S3 bucket with DynamoDB locking!*).

---

## 🌐 2. Cloudflare Tunnels (`cloudflared`) vs Traefik Reverse Proxy

In modern production and homelab setups, exposing raw ports directly to the internet is a major security risk.

```
                  [ Public Internet ]
                           │
             ┌─────────────┴─────────────┐
             ▼                           ▼
[ Cloudflare Zero-Trust Tunnel ]   [ Traefik Edge Router ]
 (Encrypted Outbound Tunnel)        (Automatic TLS / SSL)
             │                           │
             └─────────────┬─────────────┘
                           ▼
            [ Docker Containers / Services ]
```

### Key Security Benefits:
- **Zero Open Ports:** Cloudflare Tunnels establish *outbound-only* connections to Cloudflare's edge. No inbound router ports (80/443) are opened to port scanners!
- **Automatic SSL & Dynamic Routing:** Traefik uses Docker Labels to automatically discover container endpoints and issue SSL certificates via Let's Encrypt.

---

## 🔒 3. Mesh VPNs: Tailscale & WireGuard

For internal communication between isolated cloud nodes, homelabs, or developer laptops:
- **WireGuard / Tailscale:** Creates an encrypted mesh network where every node gets a stable internal IP address (`100.x.y.z`) over peer-to-peer UDP connections.
- **Zero-Trust Access:** Prevents unauthorized internal network access even if a public server gets compromised.
