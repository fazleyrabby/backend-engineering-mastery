# Idempotency, Double-Entry Ledgers, Financial Systems & Webhook Retries

> **Module:** System Design & Real-Time (Topic 3.2)  
> **Source Mapping:** `backend-roadmap.md` (Level 10: #233–#244, Level 14 & 15) & `roadmap.md` (Tier 1: #95–#132)

---

## 💳 1. What is Idempotency?

An operation is **Idempotent** if performing it multiple times yields the exact same server state as performing it once.
- `GET`, `PUT`, `DELETE` are naturally idempotent according to HTTP spec.
- `POST` is NOT naturally idempotent!

---

## 🔑 2. Idempotency Key Pattern (Preventing Double Charging)

When a client initiates a `$100` payment:

```
CLIENT                                  PAYMENT API / SERVER
  │                                               │
  ├─── POST /pay (Header: Idempotency-Key: X) ───►│ Check Redis for Key X
  │                                               │ If missing: Lock Key X in Redis,
  │                                               │ Process payment, Store Result in DB.
  │                                               │
  │◄── 200 OK (Payment Success) ──────────────────┤
  │                                               │
  │ (Network drops response! Client retries)      │
  │                                               │
  ├─── POST /pay (Header: Idempotency-Key: X) ───►│ Check Redis for Key X
  │                                               │ Key X FOUND! Return stored result!
  │◄── 200 OK (Cached Result returned!) ──────────┤ (ZERO duplicate charge!)
```

---

## ⚖️ 3. Double-Entry Bookkeeping Ledger Systems

In financial backend applications, money **must never disappear or appear from nowhere**. Every financial transaction consists of equal Debits and Credits across immutable accounts.

### The Double-Entry Equation:
$$\text{Assets} = \text{Liabilities} + \text{Equity}$$

```sql
-- Immutable Ledger Entries Schema
CREATE TABLE ledger_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(64) NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    entry_type ENUM('DEBIT', 'CREDIT') NOT NULL,
    amount_cents BIGINT NOT NULL, -- Integer minor units!
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tx_account (transaction_id, account_id)
);
```

### The Immutable Rule:
Never execute `UPDATE ledger_entries SET amount = ...`. Financial entries are **APPEND-ONLY**. If a mistake or refund happens, write a new reversing **Credit/Debit entry**!

---

## 💰 4. Financial Money Representation & Floating-Point Traps

**Rule #1:** NEVER use floating-point numbers (`FLOAT`, `DOUBLE`) for money!  
In IEEE 754 floating-point math: `0.1 + 0.2 = 0.30000000000000004`. Over 100,000 transactions, cents will literally disappear!

### Safe Representations:
1. **Integer Minor Units (Recommended):** Store `$10.50` as `1050` (cents/poisha) using `BIGINT`.
2. **SQL `DECIMAL(18, 4)`:** Explicit precision and scale arithmetic performed by the database engine.

---

## 🔄 5. Webhook Reliability & Exponential Backoff

When handling webhooks from third-party payment providers (Stripe, PayPal, Checkout.com):

1. **Immediate Ack:** Verify signature and respond with `200 OK` in <100ms.
2. **Outbox Queue:** Hand off payload to a background Redis queue worker.
3. **Exponential Backoff with Jitter for Retries:**
   $$\text{Retry Delay} = 2^{\text{attempt}} + \text{rand}(0, 1000)\text{ ms}$$
