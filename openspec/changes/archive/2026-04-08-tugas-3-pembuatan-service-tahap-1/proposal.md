# Proposal: TUGAS 3 — Pembuatan Service (Tahap 1 - Database)

## What
Create `app/Services/GenerationService.php` with a `generate($prompt)` method that:
- Calls an LLM API (configurable via env) using a strict system prompt to produce pure PostgreSQL DDL SQL.
- Saves the returned SQL to `storage/app/database.sql`.
- Executes the SQL via `DB::unprepared($sql)`.

Update `app/Jobs/GenerateArtifactsJob.php` so `handle()` invokes the `GenerationService`.

## Why
This implements the first phase of generation: convert prompts into executable database DDL and apply it. Later tasks will add Service internals, OpenAPI/Postman outputs, and additional validation.

## Scope
- No OpenAPI/Postman generation in this task.
- Keep LLM call configurable via environment variables (URL, KEY, MODEL).
