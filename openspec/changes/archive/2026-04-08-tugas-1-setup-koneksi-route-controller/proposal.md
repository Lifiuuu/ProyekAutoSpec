# Proposal: TUGAS 1 — Setup koneksi, route, & controller

## What
Implement initial wiring to accept generation requests:
- Provide `.env` configuration instructions for connecting to Supabase (Postgres) running in Docker on port `54322`.
- Add `routes/api.php` with a `POST /generate` route.
- Add `app/Http/Controllers/GeneratorController.php` with a `generate` method that validates `prompt` and returns a simple JSON response.

## Why
This sets up the minimal API surface to receive user prompts. Job/queue processing and services will be added in subsequent tasks. Keeping this task focused avoids premature coupling.

## Scope
- Only configuration instructions and minimal route/controller code.
- Do NOT create Jobs, Services, or queue workers in this change.
