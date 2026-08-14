# End-to-End System Execution: CPU, Memory, OS & How Code Runs

> **Module:** CS Fundamentals (Topic 1.0)  
> **Target:** Understanding every layer between hitting Enter on code/request and CPU execution.

---

## 🏛️ 1. The High-Level Hardware Architecture

```
+-----------------------------------------------------------------------+
|                                 CPU                                   |
|  +------------------------+  +-------------------------------------+  |
|  |     Control Unit       |  |     Arithmetic Logic Unit (ALU)     |  |
|  +------------------------+  +-------------------------------------+  |
|  | Registers (RAX, RBX..) |  | L1 Cache (32KB, ~1ns latency)       |  |
|  +------------------------+  +-------------------------------------+  |
|  | L2 Cache (512KB, ~3-5ns) | L3 Cache (Shared 16MB+, ~10-20ns)    |  |
+-----------------------------------------------------------------------+
                                  | System Bus
                                  v
+-----------------------------------------------------------------------+
|                     RAM (Main Memory, ~60-100ns)                      |
|  +------------------+  +------------------+  +---------------------+  |
|  | Code / Text Seg  |  | Stack Memory     |  | Heap Memory         |  |
|  +------------------+  +------------------+  +---------------------+  |
+-----------------------------------------------------------------------+
                                  | PCIe / NVMe Bus
                                  v
+-----------------------------------------------------------------------+
|                    Disk / Storage (SSD NVMe ~100µs)                   |
| (Source files, Database files, Compiled binaries, OS Kernel Image)    |
+-----------------------------------------------------------------------+
```

---

## 🔬 2. From Source Code to Machine Instructions

### Interpreted (PHP/Laravel) vs Compiled (C/Rust/Go)

1. **Compilation Phase (C/Go):**
   `Source Code (.go/.c)` ➔ `Lexer/Parser` ➔ `Abstract Syntax Tree (AST)` ➔ `Intermediate Representation (IR)` ➔ `Machine Code (Binary Executable: ELF / Mach-O)`.

2. **JIT / Virtual Machine Phase (PHP / Laravel / Node.js):**
   `PHP Source (.php)` ➔ `Zend Compiler` ➔ `Opcodes (Zend VM Instructions)` ➔ `Zend Engine execution (or JIT compilation to native assembly)`.

### 💡 Detailed Code Example: What PHP Opcodes Look Like Under the Hood

When PHP runs this line of code:
```php
$a = 5;
$b = 10;
$c = $a + $b;
```

The Zend Engine compiles it into 4 low-level Virtual Machine Opcodes:

```text
line     #* E I O op                           fetch          ext  return  operands
-------------------------------------------------------------------------------------
   3     0    ASSIGN                                                   $a, 5
   4     1    ASSIGN                                                   $b, 10
   5     2    ADD                                              ~2      $a, $b
         3    ASSIGN                                                   $c, ~2
   6     4    RETURN                                                   1
```

---

## 🧠 3. Memory Layout of a Running Process

When the OS executes a program, it allocates Virtual Memory space divided into distinct segments:

| Segment | Purpose | Lifetime | Growth Direction |
| :--- | :--- | :--- | :--- |
| **Text (Code)** | Executable machine assembly instructions (Read-Only) | Program execution | Static |
| **Data / BSS** | Global variables, static variables | Program execution | Static |
| **Heap** | Dynamically allocated memory (`malloc`, `new`, PHP objects) | Until freed / GC | Grows **Upward** (low to high address) |
| **Stack** | Function call frames, local variables, return addresses | Scope of function | Grows **Downward** (high to low address) |

### 💡 Concrete Code & Diagram: Stack vs Heap Allocation

```php
function processOrder(int $orderId): float 
{
    // $orderId & $taxRate are primitive values stored directly on the STACK
    $taxRate = 0.15; 
    
    // $orderObject is an INSTANCE created on the HEAP! 
    // The STACK only holds a 64-bit memory address pointer pointing to 0x7FFF82...
    $orderObject = new Order($orderId); 
    
    return $orderObject->calculateTotal() * (1 + $taxRate);
}
```

```
STACK MEMORY (Fast 1-instruction pointer push)       HEAP MEMORY (Dynamic allocations)
+------------------------------------------+          +----------------------------------+
| Frame: processOrder()                    |          | Address: 0x7FFF8200              |
| - $orderId: 42 (value)                   |          | Object: Order {                  |
| - $taxRate: 0.15 (value)                 |          |   id: 42,                        |
| - $orderObject: Pointer 0x7FFF8200 ------>|--------->|   status: 'pending',             |
+------------------------------------------+          |   items: [ ... ]                 |
                                                      | }                                |
                                                      +----------------------------------+
```

---

## ⚡ 4. CPU Execution Cycle & Context Switching

The CPU executes instructions via the **Fetch-Decode-Execute** cycle:

1. **Fetch:** Program Counter (PC / RIP register) points to the next address in RAM/L1 Cache. CPU fetches the instruction byte.
2. **Decode:** Control Unit decodes what action is required (e.g., `MOV`, `ADD`, `JMP`).
3. **Execute:** ALU performs the math/logic or register manipulation.

### Context Switch Overhead
When the OS switches execution from Process A to Process B:
- **Save State:** Registers (RIP, RSP, RAX...), CPU flags, and Page Table pointers saved to process Control Block (PCB).
- **Restore State:** Load Process B's saved state into CPU.
- **Cache Invalidation:** L1/L2 cache misses spike because CPU cache lines belong to Process A!

---

## ⚔️ 5. Senior / Staff Technical Interview Scenarios

### Q1: What happens under the hood when a function recursively calls itself infinitely?
> **Answer:** Each function invocation pushes a new **Stack Frame** onto the stack (storing return address, arguments, and local variables). Because Stack memory is fixed size per thread (typically 2MB–8MB in OS defaults), continuous pushes without popping exhaust the memory limit, resulting in a **Stack Overflow (SIGSEGV / Segment Fault)**.

### Q2: Why is accessing data from Heap slower than Stack memory?
> **Answer:** 
> 1. **Allocation Overhead:** Stack allocation is a single CPU instruction (pointer subtraction `RSP`). Heap allocation requires memory manager algorithms (finding free memory blocks, fragmentation handling).
> 2. **Cache Locality:** Stack memory is contiguous and highly likely to reside in L1/L2 CPU caches. Heap memory spans arbitrary address ranges, causing higher L3/RAM latency and cache misses.

---

## 🧪 Real-World Exploration Task

Inspect the process memory breakdown on your Mac terminal for any process:
```bash
vmmap <PID>
```
Or run the benchmark script in this repository comparing Stack/Array vs Heap/Object allocations:
```bash
php sample-codes/02_eloquent_vs_db_benchmark.php
```
