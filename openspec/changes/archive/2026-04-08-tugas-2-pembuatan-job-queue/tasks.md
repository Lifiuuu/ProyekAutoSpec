# Tasks: TUGAS 2 — Pembuatan Job Queue

- [x] Create `app/Jobs/GenerateArtifactsJob.php` accepting `prompt` in constructor and a stubbed `handle()`.
- [x] Update `app/Http/Controllers/GeneratorController.php` to dispatch the job with the incoming prompt.
- [x] Manual verification: dispatch the job and ensure request returns `{ "message": "Task queued" }`.

Stop: Do NOT implement Service or job internals in this task.
