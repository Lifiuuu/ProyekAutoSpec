# Design: TUGAS 1 — Setup koneksi, route, & controller

## .env configuration for Supabase (Docker)
Use these environment variables in your project's `.env` to connect to the Supabase Postgres instance running in Docker on port `54322`.

Add or update:

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=54322
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=postgres

Optional (Supabase REST / API values, if needed later):
SUPABASE_URL=http://localhost:54321
SUPABASE_KEY=your-service-role-or-anon-key

Notes:
- Confirm the Docker Supabase container maps Postgres to host port `54322`.
- If using a different DB user/password or database name, update values accordingly.
- Keep secrets out of version control; use environment or secret manager for production.

## Route
- Add `routes/api.php` with a `POST /generate` route bound to `GeneratorController@generate`.

## Controller
- Implement `GeneratorController::generate(Request $request)` to:
  - Validate `prompt` is required and a string.
  - Return JSON `{ "message": "Task queued" }`.
  - Do not queue a Job yet; that will be done in a later task.
