# Proposal: TUGAS 4 — Melengkapi Service & Integrasi Groq API

## What
Extend `GenerationService::generate()` to perform:

- Tahap 2: Call Groq LLM with the generated SQL and a system prompt that forces output to be a pure OpenAPI 3.0.0 JSON. Save as `storage/app/openapi.json`.
- Tahap 3: Call Groq LLM again with the same SQL and a system prompt that forces output to be a pure Postman Collection v2.1.0 JSON (including Supabase headers). Save as `storage/app/postman_collection.json`.

Add strong JSON validation and error handling for both calls.

## Why
Produce machine-readable artifacts (OpenAPI + Postman) derived from generated SQL so downstream tools and integration tests can consume them.

## Scope
- Add Groq integration and storage of artifacts. Validate JSON. Do not change existing DB execution logic.
