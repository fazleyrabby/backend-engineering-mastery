# 📈 Stock Exchange & Matching Engine Architecture

This guide details the system design, latency constraints, and deterministic logic required to build a high-performance, low-latency electronic stock exchange.

---

## 💡 Conceptual Blueprint & First Principles

A stock exchange's primary job is to match buyers (bids) and sellers (asks) fairly and atomically.

```mermaid
graph TD
    Client[Broker Client] --> Gateway[Client Gateway]
    Gateway --> OrderManager[Order Manager]
    OrderManager --> Sequencer[Sequencer]
    Sequencer --> Engine[Matching Engine]
    Engine --> MD[Market Data Publisher]
    Engine --> Reporter[Reporter Service]
```

1. **Deterministic Execution**: The matching engine must process transactions in a predictable sequence. Given the same inputs, it must yield the exact same outputs (fills/trades).
2. **State Partitioning**: The exchange must be partitioned by symbol (ticker). Each matching engine instance runs in memory and handles a subset of symbols.
3. **Execution Path Isolation**: Keep the critical trade execution path completely in-memory and non-blocking, delegating reports, reporting logs, and candlestick rendering to background streams.

---

## 🔬 Under-the-Hood Mechanics

### The L3 Order Book Data Structure
The order book contains active bids and asks sorted by price and time:
* **Bids**: Max-Heap (highest price has priority).
* **Asks**: Min-Heap (lowest price has priority).

For low-latency execution, arrays of orders at the same price are stored as doubly linked lists. The price index is maintained using a hash table or B-Tree.

```
       BIDS (Buy Orders)                  ASKS (Sell Orders)
  Price Level   [Queue List]         Price Level   [Queue List]
     $150.00    [Order A <-> Order B]   $150.05    [Order E <-> Order F]
     $149.95    [Order C]               $150.10    [Order G]
```

### Determinism & The Sequencer
To guarantee fault-tolerance and high availability, the matching engine relies on a **Sequencer**:
* Every inbound order receives a monotonically increasing sequence ID (e.g., $1, 2, 3$).
* If a matching engine node crashes, a secondary backup node reads the sequence stream, replays the orders, and reconstructs the exact same order book state without inconsistencies.

---

## 💻 Production Code & Patterns

### Simple L3 Matching Engine Loop (PHP In-Memory Concept)

```php
namespace App\Services;

class OrderBook
{
    public array $bids = []; // Sorted high to low
    public array $asks = []; // Sorted low to high

    public function matchLimitOrder(array $incomingOrder): array
    {
        $fills = [];
        $qty = $incomingOrder['quantity'];
        $price = $incomingOrder['price'];
        $side = $incomingOrder['side']; // 'BUY' or 'SELL'

        if ($side === 'BUY') {
            // Match against ask orders
            while ($qty > 0 && !empty($this->asks)) {
                $bestAskPrice = array_key_first($this->asks);
                if ($price < $bestAskPrice) {
                    break; // Price limit not met
                }

                $bestAskQueue = &$this->asks[$bestAskPrice];
                while ($qty > 0 && !empty($bestAskQueue)) {
                    $restingOrder = array_shift($bestAskQueue);
                    $matchQty = min($qty, $restingOrder['quantity']);

                    $qty -= $matchQty;
                    $restingOrder['quantity'] -= $matchQty;

                    $fills[] = [
                        'buyer_order_id' => $incomingOrder['id'],
                        'seller_order_id' => $restingOrder['id'],
                        'price' => $bestAskPrice,
                        'quantity' => $matchQty,
                    ];

                    if ($restingOrder['quantity'] > 0) {
                        // Re-add remaining ask quantity to the front of the queue
                        array_unshift($bestAskQueue, $restingOrder);
                    }
                }

                if (empty($bestAskQueue)) {
                    unset($this->asks[$bestAskPrice]);
                }
            }

            // If not fully filled, add remainder to bids
            if ($qty > 0) {
                $this->bids[$price][] = [
                    'id' => $incomingOrder['id'],
                    'quantity' => $qty,
                ];
                krsort($this->bids); // Ensure bids stay sorted descending
            }
        }
        // ... Similar logic for SELL orders matching bids
        return $fills;
    }
}
```

---

## ⚔️ Staff / Senior Interview Scenarios

### Q1: How do you achieve microsecond-level latency in an exchange?
* **Answer**:
  1. **In-Memory Matching**: Avoid database reads/writes on the critical path. The active order book resides entirely in RAM (specifically using cache-friendly arrays or structs).
  2. **Network Protocol**: Avoid HTTP/JSON overhead. Use binary protocols like FIX (Financial Information eXchange) or SBE (Simple Binary Encoding) over TCP/UDP.
  3. **Zero-Garbage Collection (GC)**: Run matching engines in languages without automatic GC cycles (e.g., C++, Rust, or optimized Go with pre-allocated memory pools) to avoid unpredictable latency spikes.
  4. **Colocation**: Host the brokers' server rigs in the same physical data center as the exchange matching engine to eliminate speed-of-light fiber optic propagation delays.

### Q2: What is Event Sourcing in the context of an Order Manager?
* **Answer**: The Order Manager keeps state transitions (e.g., `OrderPlaced`, `OrderModified`, `OrderFilled`, `OrderCancelled`) as an immutable log of events instead of updating database records directly. This allows:
  * Complete audit trails for compliance.
  * Rapid reconstructs of the current state by replaying events from a checkpoint.
  * Decoupling the write-heavy order ingestion path from read-heavy portfolio balances.
