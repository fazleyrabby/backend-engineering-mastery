# Linux I/O Models: How Servers Handle 100k+ Concurrent Connections

> **Module:** CS Fundamentals (Topic 1.2)  
> **Source Mapping:** `backend-roadmap.md` (Level 0: #16, Level 25: #511-#513) & `roadmap.md` (Tier 1: #15-#17)

---

## 🍽️ The Real-World Restaurant Analogy

Imagine a high-traffic restaurant where customers order food:

| I/O Model | Restaurant Analogy | Software Equivalent |
| :--- | :--- | :--- |
| **1. Blocking I/O** | 1 waiter takes Order A, walks to the kitchen, and **stands still doing nothing** until the chef finishes cooking Order A. Other customers must wait outside. | Traditional synchronous scripts / standard PHP-FPM without connection pooling. |
| **2. Non-Blocking I/O** | 1 waiter asks kitchen: "Is Order A ready?" (No) ➔ Asks: "Is Order A ready?" (No)... **Constantly checking in a tight loop** (CPU at 100%). | Busy-polling loops (`while(true)` checking sockets). |
| **3. I/O Multiplexing (`epoll`)** | Waiter sits at a desk. When ANY chef finishes a dish, a **bell rings** telling the waiter exactly which dish is ready (`Table 4`). The waiter delivers it instantly. | **Redis, Nginx, Node.js, Swoole, Soketi/Reverb (WebSockets)**. |

---

## 🔌 1. What is an I/O Operation & File Descriptor?

In Linux, **"Everything is a file"**. 
- A disk file, a network TCP socket (`http://...`), or a database connection is assigned an integer index by the kernel called a **File Descriptor (FD)**.

```
Incoming Web Request ➔ Network Card ➔ Linux Kernel Socket (FD #4) ➔ Application
```

An **I/O operation** (Input/Output) happens whenever your app reads/writes data to an FD (e.g., waiting for MySQL to respond over a socket or reading from disk).

---

## 🐢 2. Blocking I/O (PHP-FPM Default Model)

In Blocking I/O, when your code executes `$dbResult = $db->query("SELECT ...");`:

```
User App (PHP Thread)             Linux Kernel / MySQL Database
     │                                        │
     ├─── 1. read(FD #4) System Call ────────►│
     │                                        │ (App thread pauses/sleeps!)
     │   [ WAITING ON DISK / NETWORK ]        │ (Zero CPU usage, but thread is locked)
     │                                        │
     │◄── 2. Data Ready! Return bytes ────────┤
     ▼                                        ▼
 (App Resumes Execution)
```

### 🔴 Problem at Scale:
If each request takes 100ms and holds 1 OS thread/process, handling 10,000 concurrent users requires 10,000 threads. 
- RAM crashes because 10,000 thread stacks = 10,000 × 2MB = **20GB RAM just for idle threads!**

---

## ⚡ 3. I/O Multiplexing with `epoll` (The Secret Behind Redis & Nginx)

Instead of 10,000 threads waiting, Linux created the **`epoll` system call**. 

A **single master thread** registers 10,000 network socket FDs with the Linux Kernel:

```
+-------------------------------------------------------------------------+
|                         Linux Kernel (`epoll`)                          |
|                                                                         |
|  Monitored Sockets: [ FD#3, FD#4, FD#7, FD#12 ... FD#10000 ]             |
|                                                                         |
|  * Network packet arrives for FD#7! *                                   |
|  Kernel adds FD#7 to the "Ready List" and wakes up the master loop.     |
+-------------------------------------------------------------------------+
                                    │
                                    ▼
+-------------------------------------------------------------------------+
|                  Application Event Loop (Node/Redis/Nginx)              |
|                                                                         |
|  `epoll_wait()` returns: [ FD#7 is ready to read! ]                    |
|  App executes callback function for FD#7 without EVER blocking!         |
+-------------------------------------------------------------------------+
```

---

## 💻 4. Code Example: Synchronous vs Event-Driven Asynchronous

### Synchronous Blocking Code (Sequential)
```php
// Request 1 takes 2 seconds (Blocking I/O)
$user = fetchUserFromDb(1); 

// Request 2 MUST wait until Request 1 finishes!
$orders = fetchOrdersFromDb(1); 
```

### Asynchronous Event-Driven Code (Non-Blocking / ReactPHP / Swoole)
```php
// Both DB queries sent to kernel socket FDs simultaneously!
$userPromise = $asyncDb->queryAsync("SELECT * FROM users WHERE id = 1");
$ordersPromise = $asyncDb->queryAsync("SELECT * FROM orders WHERE user_id = 1");

// Single thread handles whichever query finishes first via epoll event loop!
Promise\all([$userPromise, $ordersPromise])->then(function ($results) {
    echo "Both done!";
});
```

---

## ⚔️ Senior / Staff Interview Questions

### Q1: Why can single-threaded Redis process 100,000 requests per second?
> **Answer:** Redis keeps all data in RAM (no disk wait) and uses Linux **`epoll` I/O multiplexing**. A single CPU thread processes incoming TCP command packets sequentially off the `epoll` ready list without wasting time context switching or waiting on blocked socket calls.

### Q2: What happens if a slow CPU-bound task (e.g., resizing a huge image or running an infinite loop) is executed inside an `epoll` event loop?
> **Answer:** It **blocks the event loop thread**! Since there is only 1 master thread processing events, no other incoming network socket (FD) can be serviced. The entire server freezes for all users until the heavy CPU task finishes.
> **Fix:** Offload heavy CPU tasks to background queue workers (Laravel Jobs / Celery / Worker Threads).
