# AI Vector Databases, pgvector, and SSE Streaming

## 1. Analogy First
Imagine a massive library where books aren't organized alphabetically, but rather by "vibe" and topic. Books about "space exploration" are physically grouped near "Mars rovers" and "astrophysics," but far away from "18th-century French poetry." 

- **Vector Embeddings:** The coordinate system that assigns each book its precise spot in the library based on its content.
- **IVFFlat (Inverted File Flat):** Dividing the library into large zones (e.g., Science, Arts) and checking the closest zone first. It's fast but you might miss a slightly more relevant book just over the border in another zone.
- **HNSW (Hierarchical Navigable Small World):** A network of express elevators and local hallways. You start at the top floor (broad topics), take an elevator down to the right wing, and then walk the local hallway to find the exact book. Highly efficient and accurate.
- **SSE (Server-Sent Events):** The librarian reading the book to you over the phone, word-by-word as they scan the page, rather than waiting to read the whole chapter before speaking.

## 2. Step-by-Step Mechanics

1. **Embedding Generation:** User text is passed to an embedding model (like OpenAI's `text-embedding-ada-002`), which returns a dense vector (e.g., an array of 1536 float values).
2. **Vector Storage:** The vector is stored in a database (like PostgreSQL with the `pgvector` extension) alongside its original text payload.
3. **Indexing:**
   - *IVFFlat* clusters vectors into lists and searches only the nearest clusters. Requires a building phase after data is inserted.
   - *HNSW* builds a multi-layered graph where upper layers have longer links (fast traversal) and lower layers have dense local links (accurate neighborhood search).
4. **Similarity Search:** When a user queries, their query is embedded. The database performs a K-Nearest Neighbors (KNN) or Approximate Nearest Neighbors (ANN) search using cosine similarity or Euclidean distance to find the closest vectors.
5. **Context Augmentation (RAG):** The retrieved text payloads are injected into a prompt for an LLM.
6. **SSE Streaming:** As the LLM generates tokens, the backend streams these tokens to the frontend over a single, long-lived HTTP connection using Server-Sent Events, creating a typewriter effect.

## 3. Annotated Python 3.11+ Code

This example demonstrates a FastAPI backend performing a vector search using `asyncpg` and `pgvector`, and then streaming a mock LLM response using Server-Sent Events.

```python
import asyncio
import json
from typing import AsyncGenerator
from fastapi import FastAPI
from sse_starlette.sse import EventSourceResponse
import asyncpg
from pgvector.asyncpg import register_vector

app = FastAPI()

# 1. Mock function to simulate generating embeddings from an AI provider
async def get_embedding(text: str) -> list[float]:
    # 2. In reality, call OpenAI/Cohere API here. Returning dummy vector.
    return [0.1, 0.2, 0.3]

# 3. Generator function that yields SSE events
async def generate_llm_stream(context: str, user_query: str) -> AsyncGenerator[dict, None]:
    # 4. Simulate a streaming LLM response based on context
    fake_response_tokens = f"Based on the context '{context}', here is your answer to '{user_query}'...".split(" ")
    
    for token in fake_response_tokens:
        await asyncio.sleep(0.1) # Simulate generation latency
        # 5. Yield dictionary matching the SSE specification
        yield {
            "event": "message",
            "data": json.dumps({"token": token + " "})
        }
    
    # 6. Send a final event to close the stream gracefully
    yield {"event": "done", "data": "[DONE]"}

@app.get("/stream_answer")
async def stream_answer(query: str):
    # 7. Embed the user's query
    query_vector = await get_embedding(query)
    
    # 8. Connect to Postgres (assume pgvector extension is enabled)
    # Note: In production, use connection pooling.
    conn = await asyncpg.connect('postgresql://user:pass@localhost/db')
    await register_vector(conn)
    
    # 9. Perform ANN search using pgvector's HNSW operator (<-> for Euclidean, <=> for Cosine)
    # The query finds the top 1 most similar document.
    row = await conn.fetchrow('''
        SELECT content 
        FROM documents 
        ORDER BY embedding <=> $1 
        LIMIT 1
    ''', query_vector)
    
    context = row['content'] if row else "No relevant context found."
    await conn.close()
    
    # 10. Return an EventSourceResponse which handles the SSE formatting
    return EventSourceResponse(generate_llm_stream(context, query))
```

## 4. Clean Mermaid Diagrams

### HNSW vs IVFFlat Architecture
```mermaid
graph TD
    subgraph "HNSW (Graph-based)"
        L2["Layer 2 (Express Links)"] --> L1["Layer 1 (Medium Links)"]
        L1 --> L0["Layer 0 (Dense Local Links)"]
        L0 --> V1["Vector Data"]
    end

    subgraph "IVFFlat (Cluster-based)"
        C["Centroids (Lists)"] --> ClusterA["Cluster A (Nearest)"]
        C --> ClusterB["Cluster B"]
        ClusterA --> V2["Vector Data"]
    end
```

### RAG and SSE Streaming Flow
```mermaid
sequenceDiagram
    participant Client as ["Client (Browser)"]
    participant API as ["FastAPI Backend"]
    participant DB as ["Postgres (pgvector)"]
    participant LLM as ["LLM Provider"]

    Client->>API: ["GET /stream?query=hello"]
    API->>API: ["Embed Query"]
    API->>DB: ["ORDER BY embedding <=> query LIMIT 1"]
    DB-->>API: ["Return nearest text context"]
    
    API->>LLM: ["Prompt with Context + Query (stream=True)"]
    
    loop ["For each token"]
        LLM-->>API: ["Chunk (Token)"]
        API-->>Client: ["SSE: data: {'token': '...'}"]
    end
    API-->>Client: ["SSE: data: [DONE]"]
```

## 5. Interview Tips

### Elevator Pitch: Vector Embeddings
1. **What it is:** High-dimensional numerical representations of text, image, or audio where semantic similarity translates to geometric closeness.
2. **Why it matters:** Enables machines to understand "meaning" rather than just keyword matching (e.g., knowing "dog" and "puppy" are related).
3. **Trade-offs:** Generating embeddings requires computational power/cost (API calls), and high dimensions consume significant RAM/storage.

### Elevator Pitch: HNSW vs IVFFlat
1. **What they are:** Indexing algorithms for Approximate Nearest Neighbor (ANN) searches in vector databases.
2. **The Difference:** IVFFlat groups vectors into clusters (requires training, lower memory, faster build). HNSW builds a multi-layered navigable graph (no training phase, extremely fast search, very high recall, but uses more memory).
3. **When to use:** Use HNSW for production systems where search speed and recall are paramount and memory is available. Use IVFFlat for simpler setups or when memory constraints are strict.

### Elevator Pitch: Server-Sent Events (SSE)
1. **What it is:** A unidirectional, long-lived HTTP connection where the server pushes text-based events to the client.
2. **Why it matters in AI:** Standard HTTP responses wait for the full LLM output to finish, causing huge latency. SSE streams tokens instantly as they are generated, improving perceived performance.
3. **SSE vs WebSockets:** SSE is strictly server-to-client and runs over standard HTTP/1.1 or HTTP/2, making it easier to load balance and proxy than bidirectional WebSockets, and perfect for LLM streaming.
