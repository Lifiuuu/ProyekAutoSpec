# Design: TUGAS 2 — Pembuatan Job Queue

## Job
- Path: `app/Jobs/GenerateArtifactsJob.php`
- Implements `ShouldQueue`.
- Traits: `Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels`.
- Constructor: accept `$prompt` (string or array) and store on public property for serialization.
- `handle()` method: include an empty `try { } catch (\\Throwable $e) { }` block — actual service invocation will be added in the next task.

## Controller changes
- Import `App\\Jobs\\GenerateArtifactsJob` in `GeneratorController`.
- Dispatch the job with the prompt payload before returning the response:

```php
GenerateArtifactsJob::dispatch($request->input('prompt'));
```

Notes:
- Keep job payload minimal (only the prompt) to ease serialization.
- Do not add Service or Job internals yet; keep `handle()` as a stub.
