# TCP/IP Stack, Handshakes, TLS 1.3 & HTTP Evolution

> **Module:** CS Fundamentals (Topic 1.3)  
> **Source Mapping:** `backend-roadmap.md` (Level 4: #96–#103) & `roadmap.md` (Tier 1: #05, #06)

---

## 🍽️ The Real-World Postal Analogy

Sending a web request is like mailing an international registered package:
1. **IP (Network Layer):** The physical postal address on the envelope (where it goes).
2. **TCP (Transport Layer):** Requiring a tracking number and signature upon arrival to guarantee no lost items (reliability).
3. **TLS (Security Layer):** Putting the letter inside an encrypted, tamper-proof safe before sending (privacy).
4. **HTTP (Application Layer):** The language written inside the letter (`GET /orders`, `POST /payments`).

---

## 🤝 1. The TCP 3-Way Handshake & 4-Way Teardown

Before data can flow over TCP, the client and server must establish a connection:

```
CLIENT                                                    SERVER
  │                                                         │
  ├─── 1. SYN (Sequence = 1000) ───────────────────────────►│ (Listen port 443)
  │                                                         │
  │◄── 2. SYN-ACK (Seq = 5000, ACK = 1001) ─────────────────┤
  │                                                         │
  ├─── 3. ACK (Seq = 1001, ACK = 5001) ────────────────────►│
  │                                                         │
  ▼                                                         ▼
[ ESTABLISHED: Round Trip Time (1 RTT) taken before any data! ]
```

### Closing a Connection (4-Way Teardown):
`FIN` ➔ `ACK` ➔ `FIN` ➔ `ACK`  
*Interview Tip:* The client enters `TIME_WAIT` state for 60 seconds to ensure the server receives the final ACK packet before reclaiming the socket port.

---

## 🔒 2. TLS 1.3 Handshake (Securing the Connection)

TLS adds encryption on top of TCP:
- **TLS 1.2:** Required 2 Round Trips (2 RTT) for encryption keys.
- **TLS 1.3:** Reduced to **1 RTT** (Key exchange happens directly during the client hello).

```
CLIENT                                                    SERVER
  │  TCP SYN                                                │
  ├─── TCP 3-Way Handshake (1 RTT) ────────────────────────►│
  │                                                         │
  ├─── ClientHello + Diffie-Hellman Key Share ─────────────►│
  │                                                         │
  │◄── ServerHello + Encrypted Extensions + Cert + Finished ┤
  │                                                         │
  ▼                                                         ▼
[ SECURE ENCRYPTED HTTP/2 APPLICATION DATA FLOWS (2 RTT total setup) ]
```

---

## 🚀 3. HTTP Evolution: HTTP/1.1 vs HTTP/2 vs HTTP/3

| Feature | HTTP/1.1 (1997) | HTTP/2 (2015) | HTTP/3 (2020+) |
| :--- | :--- | :--- | :--- |
| **Transport Layer** | TCP | TCP | **QUIC (over UDP)** |
| **Connection Model** | 1 request per TCP connection (or Head-of-Line blocking pipelining). | **Multiplexing:** Hundreds of requests over **1 single TCP socket**. | **QUIC Streams:** Independent streams over UDP. |
| **Header Format** | Plain Text | Binary (HPACK compression) | Binary (QPACK compression) |
| **Head-of-Line (HOL) Blocking** | High (Slow request blocks subsequent requests). | Resolved at HTTP level, but still exists if a TCP packet drops! | **Zero HOL Blocking** (Packet drop on Stream 1 doesn't block Stream 2). |

---

## ⚔️ Senior / Staff Interview Q&A

### Q1: What is TCP Head-of-Line (HOL) Blocking and how does HTTP/3 solve it?
> **Answer:** In HTTP/2, multiple requests share 1 TCP connection. If 1 TCP packet is lost, the kernel stops delivering bytes to the application until that missing packet is retransmitted. This pauses **ALL multiplexed HTTP requests**. HTTP/3 replaces TCP with **QUIC (UDP)**, isolating streams so lost packets only delay their specific stream!
