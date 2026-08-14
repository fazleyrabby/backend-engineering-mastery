# High-Volume Payment Gateway Integration & Webhook Resiliency

> **Module:** System Design & Real-Time (Topic 3.9)  
> **Source Mapping:** Multi-Gateway Integration (Stripe, Checkout.com, PayPal) & Outbox Pattern

---

## 💳 1. Payment Gateway Abstraction & Strategy Pattern

In production e-commerce applications, you never want your code directly tied to a single payment gateway API (`Stripe`). You build a **Payment Gateway Abstraction Layer**.

```
                           ┌──► [ Stripe Gateway Adapter ]
[ Order Checkout Service ] ┼──► [ Checkout.com Adapter ]
                           └──► [ PayPal Adapter ]
```

### Complete Production Code Architecture

```php
namespace App\Contracts;

interface PaymentGatewayInterface 
{
    public function charge(PaymentPayload $payload): PaymentResponse;
    public function refund(string $transactionId, int $amountCents): RefundResponse;
}
```

```php
namespace App\Services\Payment\Adapters;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentPayload;
use App\Contracts\PaymentResponse;
use Illuminate\Support\Facades\Http;

class CheckoutDotComAdapter implements PaymentGatewayInterface 
{
    public function charge(PaymentPayload $payload): PaymentResponse 
    {
        $response = Http::withToken(config('services.checkout.secret_key'))
            ->timeout(5) // Strict 5s timeout!
            ->post('https://api.checkout.com/payments', [
                'source' => ['type' => 'token', 'token' => $payload->token],
                'amount' => $payload->amountCents,
                'currency' => $payload->currency,
                'reference' => $payload->orderId,
            ]);

        if ($response->failed()) {
            return PaymentResponse::failed($response->json('error_type'));
        }

        return PaymentResponse::success(
            transactionId: $response->json('id'),
            status: $response->json('status')
        );
    }

    public function refund(string $transactionId, int $amountCents): RefundResponse 
    {
        // Refund implementation...
    }
}
```

---

## 🔄 2. Out-of-Order Webhook Resolution (Deferred Outbox Pattern)

Webhooks arrive **asynchronously over the public internet**. Often, a payment processor sends `payment_intent.succeeded` *before* your database finishes creating the `orders` record!

```
Webhook Receiver ──► Receives `payment_intent.succeeded` for Order #1001
                            │
                            ▼
                     Does Order #1001 exist in MySQL DB?
                     ├── YES ──► Process Payment & Fulfill Order
                     └── NO  ──► Push to Delayed Redis Queue (Delay 10s)
                                 OR Store in `deferred_webhooks` table
```

### Laravel Outbox Queue Worker Example

```php
namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessWebhookJob implements ShouldQueue 
{
    use Dispatchable, Queueable;

    public function __construct(
        public string $eventId,
        public string $orderReference,
        public array $payload
    ) {}

    public function handle() 
    {
        $order = Order::where('reference', $this->orderReference)->first();

        if (!$order) {
            // Order not found yet! Re-queue with a 10-second delay
            $this->release(10);
            return;
        }

        // Process payment state transition atomically...
        $order->markAsPaid($this->eventId);
    }
}
```
