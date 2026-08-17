# 🔐 Authentication, Authorization & APIs

This guide details best practices for user authentication (Sanctum/Passport), authorization (Gates & Policies), API resources, and structured JSON responses.

---

## 💡 Conceptual Blueprint & First Principles

When building software in Laravel, securing endpoints and formatting response payloads is crucial. Think of it like an **airport**:

*   **Authentication (The Passport Check)**: This is proving *who you are*. When you present your ID/passport, the guard verifies "Yes, you are John." In APIs, this is handled by tokens (like a wristband given to you after showing your ID).
*   **Authorization (The Boarding Pass)**: This is proving *what you are allowed to do*. Having a passport doesn't mean you can sit in the pilot's cabin or enter the first-class lounge. A Policy checks if your ticket (user permissions) allows you to enter specific gates.
*   **API Resources (The Store Receipt)**: When you buy a coffee, the store's database tracks wholesale costs, supplier emails, and margin details. But your receipt only shows the name of the coffee and the price. API Resources act as this custom receipt—they filter out internal database columns (like passwords or raw timestamps) and only show what the client needs to see.

```mermaid
graph LR
    Req[Client Request] --> Auth[Auth Middleware Sanctum]
    Auth --> Policy[Policy check: can update?]
    Policy --> Controller[Controller Logic]
    Controller --> Resource[API Resource Transformer]
    Resource --> JSON[Client Response]
```

1. **Authentication (Auth)**: Verifies *who* the client is (using session cookies or token validation with Laravel Sanctum).
2. **Authorization (Policy)**: Verifies *what* the authenticated user is allowed to do.
3. **API Resources**: Acts as a transformation layer between Eloquent Models and outward JSON responses, ensuring database schemas aren't leaked.

---

## 🔬 Under-the-Hood Mechanics

### Sanctum Token Authentication
1. The client sends credentials (email and password) to a login endpoint.
2. The controller validates credentials and creates a token: `$user->createToken('token-name')->plainTextToken`.
3. The server hashes the token and stores it in the `personal_access_tokens` table.
4. The client includes the token in the `Authorization: Bearer <token>` header of subsequent requests.
5. Sanctum's middleware validates the token hash against the database and logs in the associated user context.

---

## 💻 Production Code & Patterns

### 1. Authorization Policies
Generate a Policy (`php artisan make:policy PostPolicy --model=Post`) to guard model access:

```php
namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        // Rule: Only the user who created the post, OR an Admin, is authorized to update it.
        return $user->id === $post->user_id || $user->hasRole('admin');
    }
}
```

### 2. Using Policies in Controllers
Use the `authorize` helper in your controller to enforce policies:

```php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function update(Request $request, Post $post)
    {
        // Enforces PostPolicy@update. If unauthorized, automatically throws a 403 Forbidden exception.
        $this->authorize('update', $post);

        // Update the post with request data
        $post->update($request->all());

        return response()->json(['message' => 'Post updated successfully.']);
    }
}
```

### 3. API Resource Class
Create an API Resource (`php artisan make:resource V1/PostResource`):

```php
namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    // Formats how the Post model will be serialized into JSON
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            // Format timestamps into a standardized ISO-8601 string
            'created_at' => $this->created_at->toIso8601String(),
            
            // Conditional eager-loaded relationship: 
            // ONLY includes the user data if it has already been loaded in the query.
            // This prevents triggering a lazy loading database query (resolves N+1 issue).
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
```

---

## ⚔️ Staff / Senior Interview Scenarios

### Q1: When do you choose Laravel Sanctum over Laravel Passport?
* **Answer**:
  * **Laravel Sanctum**: Best for Single Page Applications (SPAs), mobile apps, and simple API token applications. It uses simple token hashing and cookie-based CSRF protection, making it lightweight and easy to maintain.
  * **Laravel Passport**: Best for applications that require a full OAuth2 server implementation (authorization codes, client credentials, refresh tokens). If you need to let third-party integrations authenticate against your app, choose Passport.

### Q2: How do you authorize actions on a Model before it has been instantiated (e.g. creating a model)?
* **Answer**: You define a policy method (e.g. `create()`) that checks the class reference instead of a specific model instance.
  * Controller: `$this->authorize('create', Post::class);`
  * Policy: `public function create(User $user): bool { return $user->hasRole('writer'); }`
