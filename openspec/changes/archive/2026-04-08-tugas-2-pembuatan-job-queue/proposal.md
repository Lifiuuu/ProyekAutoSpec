# Proposal: TUGAS 2 — Pembuatan Job Queue

## What
Create a background Job and wire it to the existing `GeneratorController` so prompts are processed asynchronously.

Deliverables:
- `app/Jobs/GenerateArtifactsJob.php` — Job class that accepts prompt data in constructor and has a `handle()` method with an empty try-catch block.
- Update `app/Http/Controllers/GeneratorController.php` to dispatch `GenerateArtifactsJob` with the incoming prompt before returning the JSON response.

## Why
This introduces the background queueing boundary so generation work can be processed asynchronously in a later task where the Service and Job logic will be implemented.

## Scope
- Do NOT implement the Service used by the Job yet.
- Do not create queue workers or change queue configuration in this task.
