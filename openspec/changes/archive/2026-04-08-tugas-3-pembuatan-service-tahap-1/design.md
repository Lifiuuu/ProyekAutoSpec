# Design: TUGAS 3 — GenerationService (Tahap 1)

## GenerationService
- Path: `app/Services/GenerationService.php`
- Responsibilities:
  - Build a strict system prompt instructing the LLM to return only PostgreSQL DDL statements (no explanation, no comments other than SQL comments if needed).
  - Call the LLM API endpoint specified by env `LLM_API_URL` with `LLM_API_KEY` as bearer token and optional `LLM_MODEL`.
  - Extract SQL text from the response. Support common response shapes (`choices[0].message.content`, `data[0].text`, `choices[0].text`).
  - Save the SQL to `storage/app/database.sql` via `Storage::put()`.
  - Execute SQL with `DB::unprepared($sql)`.

Notes & Constraints:
- Do NOT hardcode API keys. Use `env()` to read configuration.
- Use `try/catch` with logging around network, parsing and DB execution steps; bubble up exceptions where appropriate.
