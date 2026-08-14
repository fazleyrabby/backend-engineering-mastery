# Laravel Request Lifecycle & Middleware Pipeline

> **Module:** Laravel Internals (Topic 4.2)  
> **Source Mapping:** `backend-roadmap.md` (Level 11: #245–#254) & `roadmap.md` (Tier 1: #04, #189, #193, #194)

---

## 🔄 1. The End-to-End Request Lifecycle

When an HTTP request hits your Laravel server:

```
[ Nginx / Web Server ] 
         │ (FastCGI Pass)
         ▼
[ public/index.php ] ────── 1. Requires composer autoload.php & boots bootstrap/app.php
         │
         ▼
[ Service Providers ] ──── 2. Calls register() then boot() on all Service Providers
         │
         ▼
[ HTTP Kernel ] ────────── 3. Passes Request through Global Middleware Pipeline
         │
         ▼
[ Router & Route ] ──────── 4. Matches route, runs Route Middleware Pipeline
         │
         ▼
[ Controller Action ] ──── 5. Resolves dependencies via Container & executes code
         │
         ▼
[ HTTP Response ] ──────── 6. Sends headers/body back to client & executes terminate()
```

---

## 🧪 2. How Middleware Pipeline Works Under the Hood (Pipes & Closures)

Laravel Middleware relies on the **Decorator / Pipeline Pattern** (using PHP `array_reduce` and Closures).

When you write a Middleware:
```php
class CheckTokenMiddleware {
    public function handle(Request $request, Closure $next)
    {
        // 1. BEFORE Request logic
        if (!$request->hasHeader('X-API-KEY')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 2. Pass request down to the next pipe in onion layer
        $response = $next($request);

        // 3. AFTER Response logic (e.g. adding security headers)
        $response->headers->set('X-Security-Header', 'Active');

        return $response;
    }
}
```

### The Onion Architecture:
```
Request ──► [ Global Middleware 1 ] ──► [ Route Middleware 2 ] ──► [ Controller ]
                                                                        │
Response ◄─ [ Global Middleware 1 ] ◄── [ Route Middleware 2 ] ◄────────┘
```
