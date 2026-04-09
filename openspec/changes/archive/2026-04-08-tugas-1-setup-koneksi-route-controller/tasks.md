# Tasks: TUGAS 1 — Setup koneksi, route, & controller

1. Add `.env` configuration instructions for Supabase (post-update only — do not commit secrets).
2. Create `routes/api.php` and register `POST /generate` route.
3. Create `app/Http/Controllers/GeneratorController.php` with `generate` method:
   - Validate `prompt` is required.
   - Return JSON `{ "message": "Task queued" }`.
 - [x] Add `.env` configuration instructions for Supabase (post-update only — do not commit secrets).
 - [x] Create `routes/api.php` and register `POST /generate` route.
 - [x] Create `app/Http/Controllers/GeneratorController.php` with `generate` method:
    - Validate `prompt` is required.
    - Return JSON `{ "message": "Task queued" }`.
 - [x] Manual verification: run the app and curl the endpoint to confirm validation and response.

Stop: do NOT create Jobs or Services in this task.

Verification examples:

curl -X POST http://localhost/api/generate -H "Content-Type: application/json" -d '{"prompt":"Hello"}'
