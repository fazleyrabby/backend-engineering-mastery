# High-Volume Payment Gateway Integration & Webhooks

> **Module:** System Design & Real-Time (Topic 3.9)  

---

## 💳 1. Payment Gateway Abstraction

**Analogy:** Imagine having a TV that only works with one specific brand of remote control. If that remote breaks, you can't watch TV! Instead, you want a "Universal Remote" that can control any TV.
In code, tying your logic directly to `Stripe` is dangerous. If Stripe goes down, you want to easily swap to `PayPal`. We use the **Strategy Pattern** to create our "Universal Remote".

```mermaid
classDiagram
    class UniversalGateway {
        <<interface>>
        +charge(money)
    }
    class StripeAdapter {
        +charge(money)
    }
    class PayPalAdapter {
        +charge(money)
    }
    UniversalGateway <|-- StripeAdapter
    UniversalGateway <|-- PayPalAdapter
    CheckoutApp --> UniversalGateway
```

### Python 3.11+ Adapter Pattern

```python
import asyncio
from abc import ABC, abstractmethod
from dataclasses import dataclass

@dataclass
class PaymentInfo:
    amount: int
    card_token: str

# 1. Our "Universal Remote" interface
class PaymentGateway(ABC):
    @abstractmethod
    async def charge(self, info: PaymentInfo) -> str:
        pass

# 2. The Stripe-specific implementation
class StripeAdapter(PaymentGateway):
    async def charge(self, info: PaymentInfo) -> str:
        # Always wrap external network calls in a timeout!
        # If Stripe is hanging, we don't want our whole app to freeze.
        async with asyncio.timeout(3.0):
            # ... send HTTP POST to Stripe ...
            await asyncio.sleep(0.5) 
            return "success_stripe_123"
```

---

## 🔄 2. The Outbox Pattern for Webhooks

**Analogy:** You order a package (Customer checkout) and the mail carrier rings your bell to deliver it (Stripe Webhook). 
**The Problem:** What if the carrier rings your bell *before* you've even had time to walk home from the store? You aren't there to receive it!
In systems, Stripe might send the "Payment Success" webhook *before* your database finishes saving the new order. 

**The Solution:** Have the mail carrier drop the package in a secure drop-box (Outbox Queue) on your porch. You can process it when you are ready.

### Step-by-Step Flow
1. **Receive & Accept:** Stripe sends the webhook. We immediately return `200 OK`.
2. **Store Safely:** We save the raw JSON payload into a database table (`webhook_events`).
3. **Process Later:** A background worker reads the table and processes it at its own pace.

### SQL Implementation

```sql
-- 1. Create a table to act as our "Drop-box"
CREATE TABLE webhook_events (
    id UUID PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    payload JSONB NOT NULL,
    status VARCHAR(20) DEFAULT 'PENDING',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. Add an index to quickly find ONLY the pending ones
CREATE INDEX idx_pending_webhooks ON webhook_events(status) WHERE status = 'PENDING';
```

---

## 🚦 3. The Payment Lifecycle State Machine

**Analogy:** Think of booking a hotel room.
1. **Created:** You make the reservation online.
2. **Requires Action:** The hotel needs you to complete a 3D Secure verification or 2FA.
3. **Authorized (Hold):** The hotel puts a temporary $100 "hold" on your card. No money has left your bank yet, but you can't spend that $100 elsewhere.
4. **Captured (Charge):** When you check out, the hotel finalizes the bill and actually takes the money.
5. **Void:** You cancel your reservation before checking out. The hotel releases the $100 hold.
6. **Refund:** You already paid, but later complain about the room. The hotel sends money *back* to you.

### Two-Step Payments (Authorization vs Capture)
Often, e-commerce stores **authorize** a card when the order is placed, but only **capture** the funds when the physical item is actually shipped. If the item goes out of stock, they simply **void** the authorization without paying refund processing fees.

### Refund Processing & Voids
- **Void:** Canceling an authorization *before* capture. It is fast and usually incurs no processing fees.
- **Refund:** Reversing a captured payment. This requires transferring money back across the banking network and often involves **ledger reversals** (creating negative bookkeeping entries to balance the accounts). Refunds can be full or partial.

### The State Diagram

```mermaid
stateDiagram-v2
    [*] --> created
    created --> requires_action: "3D Secure / MFA"
    requires_action --> authorized: "User authenticates"
    created --> authorized: "Valid Card"
    
    authorized --> captured: "Shipment confirmed"
    authorized --> voided: "Order cancelled (Void)"
    
    captured --> settled: "Funds hit bank account"
    captured --> refunded: "Customer returns item"
    captured --> disputed: "Customer initiates chargeback"
    
    created --> failed: "Declined (Insufficient funds)"
    requires_action --> failed: "Failed authentication"
```

---

## ⚔️ 4. Interview Tips

### Q: Stripe is down and returning 500 Errors. How do you save sales?
**3-Point Pitch:**
1. **The Risk:** Relying on a single provider means their downtime is your downtime.
2. **Circuit Breaker Pattern:** We wrap our Stripe calls in a "Circuit Breaker". If Stripe fails 5 times, the breaker "opens" and stops trying Stripe.
3. **Failover:** While the breaker is open, all traffic is instantly routed to a backup provider (like PayPal or Adyen) so we don't lose revenue.

### Q: What if a webhook processing crashes halfway through?
**3-Point Pitch:**
1. **The Danger:** If a webhook updates the inventory but crashes before marking the order as "Paid", our data is corrupted.
2. **ACID Transactions:** We wrap all database updates in a single SQL Transaction (`BEGIN ... COMMIT`).
3. **All or Nothing:** If it crashes halfway, the database rolls back everything, ensuring our system state is always consistent. We can then retry safely later.
