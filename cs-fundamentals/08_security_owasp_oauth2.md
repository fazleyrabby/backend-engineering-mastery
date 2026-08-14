# Web Application Security, OWASP Top 10 & OAuth 2.0

> **Module:** CS Fundamentals (Topic 1.8)  
> **Source Mapping:** `backend-roadmap.md` (Level 18: #381–#404) & `roadmap.md` (Tier 1: #215–#233)

---

## 🛡️ 1. OWASP Top 10 Core Security Vulnerabilities

| Vulnerability | Attack Vector | Prevention Mechanism |
| :--- | :--- | :--- |
| **SQL Injection (SQLi)** | Attacker injects SQL snippets into input fields (e.g., `' OR '1'='1`). | Use **Prepared Statements / Parameterized Queries** (PDO, Eloquent). Never concatenate raw input strings into SQL! |
| **Cross-Site Scripting (XSS)** | Attacker injects malicious JS into your page to steal cookies/tokens. | Escape HTML output (`{{ $var }}` in Blade), set `HttpOnly` on cookies, use Content Security Policy (CSP). |
| **Cross-Site Request Forgery (CSRF)** | Malicious site tricks logged-in user's browser into sending unauthorized requests. | Use **CSRF Tokens** (`_token` header/input) and `SameSite=Strict` cookie flags. |
| **Server-Side Request Forgery (SSRF)** | Attacker tricks backend into making HTTP requests to internal networks (e.g., `http://169.254.169.254/metadata`). | Validate and whitelist outgoing request URLs. Block internal IP address ranges (`127.0.0.1`, `10.0.0.0/8`). |

---

## 🔑 2. Password Hashing vs Encryption

- **Encryption (Symmetric/Asymmetric):** Reversible! Used for data in transit/rest where original plain text must be recovered.
- **Hashing (Argon2id / Bcrypt):** **One-way deterministic function!** Never encrypt passwords; always hash them using salted algorithms (`Bcrypt` or `Argon2id`).

---

## 🔑 3. OAuth 2.0 & OpenID Connect (OIDC) Lifecycle

OAuth 2.0 is an **Authorization** framework (granting access to resources without sharing passwords):

```
CLIENT (App)               RESOURCE OWNER (User)           AUTHORIZATION SERVER (Google/GitHub)
   │                               │                                 │
   ├─── 1. Redirect to Auth Login ─►│                                 │
   │                               ├─── 2. Approve Access Scope ────►│
   │                                                                 │
   │◄── 3. Authorization Code (Callback URL) ────────────────────────┤
   │                                                                 │
   ├─── 4. Exchange Auth Code + Client Secret ──────────────────────►│
   │                                                                 │
   │◄── 5. Return Access Token & Refresh Token ──────────────────────┤
```
