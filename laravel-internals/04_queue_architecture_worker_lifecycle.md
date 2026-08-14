# Laravel Queue Architecture, Horizon & Worker Lifecycle

> **Module:** Laravel Internals (Topic 4.4)  
> **Source Mapping:** `backend-roadmap.md` (Level 11: #265–#269 & Level 12) & `roadmap.md` (Tier 1: #160–#173)

---

## ⚙️ 1. How Laravel Queue Job Dispatching Works

When you run `ProcessPaymentJob::dispatch($order)`:

```
[ App Code ] ──1. Serializes Job Object ──► [ Redis / Queue Driver ]
                                                      │ (Pushes JSON Payload)
                                                      ▼
                                            [ `queue:work` Worker Process ]
                                                      │ (Pulls Payload & Deserializes)
                                                      ▼
                                            [ Executes handle() Method ]
```

### The Serialized Payload:
Laravel converts job class instances and Eloquent models into JSON strings containing model IDs (`Illuminate\Contracts\Database\ModelIdentifier`). When worker pulls the job, it re-queries the DB (**SerializesModels** trait).

---

## 🔄 2. Worker Lifecycle & Memory Leak Prevention

`php artisan queue:work` runs a long-lived CLI loop:

```php
while (true) {
    $job = $popNextJob();
    if ($job) {
        $job->fire();
    }
    
    // Check if worker hit --memory limit (default 128MB) or if code changed
    if ($memoryExceeded() || $shouldStop()) {
        exit(); // Supervisor re-spawns fresh worker process!
    }
}
```

*Worker Tip:* Always use `queue:restart` after deploying new code in production so workers reload the updated PHP code into RAM!
