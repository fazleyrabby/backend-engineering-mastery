# Eloquent ORM Mechanics, N+1 Problem & Query Builder

> **Module:** Laravel Internals (Topic 4.3)  
> **Source Mapping:** `backend-roadmap.md` (Level 11: #255–#262) & `roadmap.md` (Tier 1: #46, #196–#200)

---

## ⚡ 1. What Eloquent is Doing Under the Hood

Eloquent is an **Active Record ORM**. Each Eloquent model wraps a database table row and delegates queries to the underlying **Query Builder (PDO Connection)**.

### Model Hydration Overhead:
When you execute `User::all()`, Eloquent:
1. Executes `SELECT * FROM users` via PDO.
2. Loops through raw arrays and **hydrates** each row into a new `User` PHP class instance with attributes, mutation tracking, dynamic relations, and event listeners.

*Performance Tip:* For large read-only reports, use `DB::table('users')->get()` (raw stdClass objects) to avoid Eloquent hydration memory overhead!

---

## 🚨 2. The N+1 Query Problem & Eager Loading

The N+1 problem occurs when accessing a relationship inside a loop without preloading it.

### The Bad Code (N+1 Queries):
```php
$posts = Post::all(); // 1 query: SELECT * FROM posts (returns 100 posts)

foreach ($posts as $post) {
    echo $post->author->name; // 100 separate queries: SELECT * FROM users WHERE id = ?
}
// Total Queries = 1 + 100 = 101 queries!
```

### The Fix (Eager Loading with `with()`):
```php
$posts = Post::with('author')->get(); // 2 queries total!

// Query 1: SELECT * FROM posts;
// Query 2: SELECT * FROM users WHERE id IN (1, 2, 3, ... 100);
```

### Preventing N+1 in Production:
In `AppServiceProvider::boot()`:
```php
Model::preventLazyLoading(!app()->isProduction());
```
