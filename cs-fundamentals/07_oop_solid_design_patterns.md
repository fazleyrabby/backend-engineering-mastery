# OOP Principles, SOLID, Clean Architecture & Design Patterns

> **Module:** CS Fundamentals (Topic 1.7)  
> **Source Mapping:** `backend-roadmap.md` (Level 1 & Level 21) & `roadmap.md` (Tier 1: #01, #02, #03)

---

## 🏛️ 1. The 4 Core Principles of OOP (Explained with Backend Examples)

1. **Encapsulation:** Bundling data and methods into a single unit while restricting direct external access to private properties (`protected/private`). *Example:* Hiding a `$balance` variable inside a `BankAcccount` class and exposing only `$account->deposit($amount)` with validation logic.
2. **Abstraction:** Exposing only essential interface features while hiding complex internal implementation details. *Example:* `$paymentGateway->charge($amount)` hides HTTP connections, payload signatures, and SSL handshakes.
3. **Inheritance:** Creating new classes based on existing ones to share behavior. (*Note:* Prefer **Composition over Inheritance** to prevent tight coupling!).
4. **Polymorphism:** Treating objects of different classes through a single common interface. *Example:* Passing a `StripeAdapter` or `PayPalAdapter` into a service that expects `PaymentGatewayInterface`.

---

## 🛡️ 2. The S.O.L.I.D Principles

| Principle | Meaning | Real-World Violation | Clean Solution |
| :--- | :--- | :--- | :--- |
| **S**ingle Responsibility | A class should have only **one reason to change**. | `UserController` handles validation, database SQL queries, sending emails, and formatting JSON responses. | Extract logic into `UserRegistrationService`, `MailerService`, and API Resources. |
| **O**pen/Closed | Open for extension, **closed for modification**. | Using `switch($gateway)` statements that require editing existing code every time a new payment provider is added. | Create an `Interface` and implement new classes without touching core logic. |
| **L**iskov Substitution | Subtypes must be substitutable for base types without breaking the app. | `ReadOnlyDatabase` extending `Database` but throwing exceptions on `save()`. | Separate interfaces into `ReadableDatabaseInterface` and `WritableDatabaseInterface`. |
| **I**nterface Segregation | Clients shouldn't be forced to depend on methods they don't use. | A monolithic `RepositoryInterface` with 50 methods for reports, users, and audit logs. | Break into small, targeted interfaces (`UserRepositoryInterface`). |
| **D**ependency Inversion | Depend on **abstractions**, not concrete classes. | `OrderService` directly instantiating `new StripeClient()`. | Inject `PaymentGatewayInterface` via constructor. |

---

## 🎨 3. Essential Design Patterns for Backend Architects

### Factory Pattern
Creates objects without specifying the exact concrete class directly.
```php
class PaymentGatewayFactory {
    public static function make(string $provider): PaymentGatewayInterface {
        return match($provider) {
            'stripe' => new StripeGateway(config('stripe.key')),
            'paypal' => new PayPalGateway(config('paypal.key')),
            default => throw new InvalidArgumentException("Unsupported provider"),
        };
    }
}
```

### Strategy Pattern
Swaps algorithms or business rules at runtime depending on context (e.g., dynamic tax calculation strategies for US vs EU orders).

### Observer Pattern
Publishes state changes to multiple listeners asynchronously without coupling the main class to the downstream actions (e.g., Laravel Events & Listeners).
