# CI/CD Pipelines, Zero-Downtime Deployments & Security

> **Module:** Cloud & DevOps (Topic 5.2)  
> **Source Mapping:** `backend-roadmap.md` (Level 26: #533–#545) & `roadmap.md` (Tier 2: #282–#285)

---

## 🚀 1. Continuous Integration & Deployment (CI/CD)

- **Continuous Integration (CI):** Automatically running linting, static analysis (`phpstan`), and automated unit/feature tests (`pest`/`phpunit`) on every Git pull request.
- **Continuous Deployment (CD):** Automatically building and deploying passing code to staging/production.

---

## 🔄 2. Blue-Green vs. Canary vs. Rolling Deployments

How to deploy code updates without dropping incoming user traffic:

```
Blue-Green Deployment:
                                    ┌──► [ Blue Environment (Current v1.0) ]
[ Traffic / Load Balancer ] ───────┤
                                    └──► [ Green Environment (New v2.0 - Testing) ]
```

1. **Blue-Green Deployment:** Run 2 identical production environments. Deploy v2.0 to Green. Once verified, switch Load Balancer traffic from Blue ➔ Green in **<1 second**.
2. **Canary Deployment:** Route 5% of traffic to new v2.0 code. If error rates remain 0%, gradually scale up traffic to 100%.

---

## 🔒 3. Zero-Downtime Database Migrations

**Rule:** Never run destructive database migrations (e.g. `ALTER TABLE DROP COLUMN`) during deployment while v1.0 code is still running!

### The Expand & Contract Pattern:
To rename a column `full_name` ➔ `name`:
1. **Phase 1 (Expand):** Add new column `name`. App writes to BOTH `full_name` and `name`.
2. **Phase 2 (Migrate):** Backfill historical data in background.
3. **Phase 3 (Contract):** Update code to read from `name`. Drop old `full_name` column.
