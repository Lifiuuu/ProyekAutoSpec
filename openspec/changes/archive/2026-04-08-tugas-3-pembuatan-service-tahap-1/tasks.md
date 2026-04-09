# Tasks: TUGAS 3 — PEMBUATAN SERVICE (TAHAP 1 - DATABASE)

- [x] Create `app/Services/GenerationService.php` with `generate($prompt)` implementing:
  - LLM API call using `LLM_API_URL` and `LLM_API_KEY` env vars.
  - Extract SQL from LLM response.
  - Save SQL to `storage/app/database.sql`.
  - Execute SQL via `DB::unprepared($sql)`.
- [x] Update `app/Jobs/GenerateArtifactsJob.php` to call `GenerationService::generate($this->prompt)` inside `handle()`.
- [x] Manual verification: dispatch a job with a safe prompt (or run service directly) and confirm `database.sql` is written (do not run untrusted SQL on production).

Stop: Do NOT add OpenAPI/Postman logic yet.
