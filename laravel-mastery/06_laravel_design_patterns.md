# 📐 Laravel Design Patterns & Architecture

This guide covers production-grade architectural design patterns used in Laravel applications, including the Service-Repository pattern, Action Classes, Data Transfer Objects (DTOs), and the Pipeline pattern.

---

## 💡 Conceptual Blueprint & First Principles

In large-scale enterprise Laravel applications, keeping all logic in controllers or models leads to "fat controllers" or "spaghetti models." Standardizing design patterns separates concerns cleanly:

*   **Service-Repository (The Chef & The Storekeeper)**:
    *   *Repository (The Storekeeper)*: Knows exactly where the ingredients (data) are located in the warehouse (Database) and fetches them. It doesn't know what you are cooking.
    *   *Service (The Chef)*: Receives the ingredients from the storekeeper and applies the business recipe (e.g., calculates discounts, sends confirmation emails, completes checkout).
*   **Action Classes (The Single-Purpose Gadget)**: Instead of a giant food processor (a Service class with 20 different methods for updating, deleting, registering, resetting), you have a collection of single-purpose gadgets (like a garlic press or a cherry pitter). An Action class does **one** single task (e.g., `CreateUserAction`) and is triggered with a single button (`__invoke`).
*   **Data Transfer Objects - DTOs (The Molded Travel Box)**: When shipping fragile items, you don't throw them loose into a shipping box (raw arrays like `['name' => 'John']`). You place them in a custom-molded tray where each item has an exact slot with a specific size and type (strongly typed properties).
*   **Pipelines (The Factory Assembly Line)**: Imagine an assembly line for a toy. Stage 1 checks the parts (VerifyStock), Stage 2 paints it (CalculateTax), Stage 3 packages it (ChargePayment). The toy is passed down the line, and if any stage fails, the line stops.

```mermaid
graph TD
    Request[HTTP Request] --> Controller[Controller / Action]
    Controller --> DTO[Data Transfer Object]
    DTO --> Pipeline[Pipeline: Validate -> Process -> Notify]
    Pipeline --> Service[Service Layer]
    Service --> Repository[Repository Layer]
    Repository --> Database[(Database)]
```

* **Service-Repository**: Separates data storage queries (Repository) from business rules (Service).
* **Action Classes**: Replaces fat services with single-responsibility classes containing a single public method (usually `__invoke`).
* **DTOs**: Replaces raw arrays with strongly typed objects to pass data between application layers safely.
* **Pipelines**: Passes data sequentially through a series of stackable tasks (stages).

---

## 🔬 Under-the-Hood Mechanics

### The Laravel Pipeline (`Illuminate\Pipeline\Pipeline`)
Laravel's Middleware runs on the Pipeline design pattern.
*   **Mechanics**: You pass a target object (`send($passable)`) through an array of classes (`through($pipes)`). Each class (pipe) receives the object and a closure (`$next`). The pipe performs its operation and calls `$next($passable)` to pass the data to the next stage.

---

## 💻 Production Code & Patterns

### 1. Action Classes & DTO Pattern
Instead of passing raw request arrays to services, parse them into a type-safe DTO and execute a single Action.

```php
// app/DTOs/CreateUserDTO.php
namespace App\DTOs;

use App\Http\Requests\RegisterRequest;

class CreateUserDTO
{
    // PHP 8 Constructor Property Promotion: Defines read-only typed properties
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}

    // Factory method to safely extract validated request data and return a clean DTO instance
    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password')
        );
    }
}
```

```php
// app/Actions/CreateUserAction.php
namespace App\Actions;

use App\DTOs\CreateUserDTO;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    // __invoke allows the class to be called directly as a function: $createUserAction($dto)
    public function __invoke(CreateUserDTO $dto): User
    {
        // Execute the single responsibility of creating a user in the database
        return User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password), // Safe password hashing
        ]);
    }
}
```

```php
// app/Http/Controllers/RegisterController.php
namespace App\Http\Controllers;

use App\Actions\CreateUserAction;
use App\DTOs\CreateUserDTO;
use App\Http\Requests\RegisterRequest;

class RegisterController extends Controller
{
    // The controller is thin: it receives the request, builds a DTO, triggers the action, and returns JSON
    public function __invoke(RegisterRequest $request, CreateUserAction $action)
    {
        // Map request parameters into a structured DTO, then execute the Action
        $user = $action(CreateUserDTO::fromRequest($request));

        return response()->json(['message' => 'User registered successfully.'], 201);
    }
}
```

### 2. The Pipeline Pattern
Use pipelines for multi-step workflows like order checkouts:

```php
namespace App\Services;

use App\Models\Order;
use Illuminate\Pipeline\Pipeline;

class OrderProcessor
{
    public function process(Order $order): Order
    {
        // Send the order object sequentially through the pipeline stages (pipes)
        return app(Pipeline::class)
            ->send($order)
            ->through([
                \App\Pipes\VerifyStock::class,
                \App\Pipes\CalculateTax::class,
                \App\Pipes\ChargePayment::class,
                \App\Pipes\GenerateInvoice::class,
            ])
            // If all pipes complete successfully, return the processed order
            ->then(fn (Order $order) => $order);
    }
}
```

A pipe class structure:
```php
namespace App\Pipes;

use App\Models\Order;
use Closure;

class VerifyStock
{
    // Each pipe handles the data, then forwards it to the next step
    public function handle(Order $order, Closure $next)
    {
        // 1. Perform step logic (verify stock)
        if ($order->items->isEmpty()) {
            throw new \Exception("Cannot process empty order.");
        }

        // 2. Forward to the next pipe in the pipeline sequence
        return $next($order);
    }
}
```

---

## ⚔️ Staff / Senior Interview Scenarios

### Q1: Is the Repository Pattern always necessary in Laravel?
* **Answer**: No. Eloquent is already an implementation of the Active Record pattern, providing a powerful query builder. Wrapping Eloquent in a Repository interface is often redundant ("over-abstraction") unless:
  * You expect to swap ORMs or data sources entirely (e.g., MySQL to MongoDB).
  * You need to decouple queries for testing without hitting a database (though mocking Eloquent is simple).
  * **Alternative**: Use Local Query Scopes to modularize and reuse database queries.

### Q2: What is the primary benefit of Action Classes over Service Classes?
* **Answer**: Service classes tend to accumulate methods over time and turn into "God classes" containing unrelated actions (e.g., `UserService` handling creation, editing, profile image uploading, password resets, and sending emails). Action classes enforce the **Single Responsibility Principle (SRP)**. Every action is a single file, making it highly testable, mockable, and easy to locate.
