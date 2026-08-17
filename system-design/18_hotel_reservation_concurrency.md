# 🏨 Hotel Reservation & High-Concurrency Booking Systems

This guide details the system design, data modeling, and transaction safety required to build a resilient, high-concurrency booking engine (applicable to hotels, flights, and ticket sales).

---

## 💡 Conceptual Blueprint & First Principles

Booking systems require strict consistency (no double bookings) alongside high availability for browsing inventories. Think of it like this:

*   **Pessimistic Locking (The Ticket Counter Queue)**: When you ask for a ticket, the teller holds it in their hand and won't let anyone else touch it until you pay or walk away. This ensures no one else can buy it, but everyone behind you has to wait in a slow line.
*   **Optimistic Locking (The Document Version Check)**: Two editors download the same document (Version 1) to edit. Editor A finishes first and uploads it; the system saves it as Version 2. Editor B tries to upload their changes, but the system checks: "Wait, you are submitting changes based on Version 1, but the document is already Version 2!" The system rejects Editor B's edit, and Editor B must refresh and try again.
*   **Database Check Constraints (The Water Bucket Overflow)**: Imagine a bucket that can hold exactly 10 gallons. No matter how fast or how many people pour water in, the bucket physically overflows if it goes past 10. A SQL `CHECK` constraint is a database-level bucket guardrail—if any transaction tries to add inventory past the limit, the database throws a hard block immediately.

```mermaid
graph TD
    User[Client Browser] --> API[Public API Gateway]
    API --> Cache[Redis Inventory Cache]
    API --> ResService[Reservation Service]
    ResService --> DB[(Relational DB Master)]
    DB --> Replica[(Read Replicas)]
```

1. **Inventory Scoping**: Users reserve a room *type* (e.g., Deluxe King) for a specific date range, not a specific room number (which is assigned at check-in).
2. **Access Patterns**: High read-to-write ratio. Browsing room details/rates is heavy; bookings are sparse but critical.
3. **Transaction Safety**: Must adhere to ACID principles to ensure payments match inventory decreases.

---

## 🔬 Under-the-Hood Mechanics

### The Inventory Table Design
To manage room counts dynamically across dates, we pre-populate the inventory:

| hotel_id | room_type_id | date       | total_inventory | total_reserved |
|----------|--------------|------------|-----------------|----------------|
| 101      | 2001         | 2026-08-17 | 50              | 12             |
| 101      | 2001         | 2026-08-18 | 50              | 15             |

Checking availability for $N$ rooms over a date range:
```sql
SELECT date, total_inventory, total_reserved 
FROM room_type_inventory 
WHERE hotel_id = ? AND room_type_id = ? AND date BETWEEN ? AND ?;
```
For *every* date in the range, the system verifies:
$$\text{total\_reserved} + N \le \text{total\_inventory} \times 1.10 \quad (\text{allowing 10\% overbooking})$$

---

## 💻 Production Code & Patterns

### Solving Concurrency: Locking Options

#### Option 1: Pessimistic Locking (`SELECT FOR UPDATE`)
Locks selected rows until the transaction commits, preventing concurrent modifications.

```php
use Illuminate\Support\Facades\DB;

public function reservePessimistic(int $hotelId, int $roomTypeId, string $start, string $end, int $qty)
{
    // Start database transaction
    return DB::transaction(function () use ($hotelId, $roomTypeId, $start, $end, $qty) {
        
        // 1. Fetch matching dates and LOCK the rows (Pessimistic Lock)
        // No other connection can edit or lock these rows until our transaction completes.
        $inventories = DB::table('room_type_inventory')
            ->where('hotel_id', $hotelId)
            ->where('room_type_id', $roomTypeId)
            ->whereBetween('date', [$start, $end])
            ->lockForUpdate() // <- Instructs database to lock these rows (SELECT ... FOR UPDATE)
            ->get();

        // 2. Verify availability under the lock
        foreach ($inventories as $inv) {
            // Allows up to 10% overbooking limit
            if (($inv->total_reserved + $qty) > ($inv->total_inventory * 1.10)) {
                throw new \Exception("Insufficient inventory for date: {$inv->date}");
            }
        }

        // 3. Increment reserved quantity
        DB::table('room_type_inventory')
            ->where('hotel_id', $hotelId)
            ->where('room_type_id', $roomTypeId)
            ->whereBetween('date', [$start, $end])
            ->increment('total_reserved', $qty);

        return true; // Transaction commits successfully, locks are released
    });
}
```
* **Trade-off**: Highly reliable but creates database lock queues. Scalability drops under high contention.

#### Option 2: Optimistic Locking (Version Column)
Allows simultaneous reads, but verifies the row version hasn't changed before writing.

```php
// Step 1: Attempt to write the change, but ONLY if the version matches the one we fetched
// $originalVersion is what we read earlier. If another query updated it first, the version is now higher.
$updated = DB::table('room_type_inventory')
    ->where('id', $invId)
    ->where('version', $originalVersion) // Ensure no one edited this row since we fetched it
    ->update([
        'total_reserved' => $newReserved,
        'version' => $originalVersion + 1 // Increment version number
    ]);

if (!$updated) {
    // Conflict detected: someone else got in first! Roll back and retry.
    throw new ConcurrentModificationException("Inventory updated by another request.");
}
```
* **Trade-off**: Scalable with low contention. With high traffic, rollback loops degrade performance.

#### Option 3: Database Check Constraints
The database prevents updates that violate inventory rules.

```sql
-- Enforces a strict validation limit directly inside the database engine.
-- If any update attempts to push total_reserved past total_inventory * 1.10,
-- the database rejects the command and throws an error immediately.
ALTER TABLE room_type_inventory 
ADD CONSTRAINT check_inventory_limit 
CHECK (total_reserved <= CAST(total_inventory * 1.10 AS UNSIGNED));
```
Any transaction trying to over-allocate immediately throws a database error, allowing the application to catch the constraint exception and fail gracefully.

---

## ⚔️ Staff / Senior Interview Scenarios

### Q1: How do you scale a booking database to handle Booking.com levels of traffic?
* **Answer**:
  1. **Sharding**: Shard the relational database by `hash(hotel_id) % num_shards`. Since reservations, rates, and inventory queries are scoped by hotel, all transactions stay local to a single shard.
  2. **Inventory Cache**: Offload reads and initial booking checks to Redis. Use a Lua script to atomically check and decrement inventory:
     ```lua
     local key = KEYS[1] -- hotel_room_date
     local qty = tonumber(ARGV[1])
     local limit = tonumber(ARGV[2])
     local current = tonumber(redis.call('get', key) or "0")
     if current + qty <= limit then
         redis.call('incrby', key, qty)
         return 1
     else
         return 0
     end
     ```
     Upon successful Redis reservation, push the booking asynchronously to a queue for database persistence.
