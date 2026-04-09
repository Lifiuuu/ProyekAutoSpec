**Design**

Goal: Make `GeneratorController` call `GenerationService` and return the generated `runId` (or structured result) so the frontend can show download links for the generated artifacts.

1) Requirements & assumptions
- `GenerationService::generate()` must provide the unique `runId` used to name artifacts in `storage/app/generations/` (current implementation creates an internal `$runId`). If it does not, update the service to return an object/array with `runId` and `sql` (or add a `getLastRunId()` accessor).
- Controller must locate files at `storage_path('app/generations/{$runId}.sql')`, `...openapi.json`, `...postman.json`.
- Frontend may call controller via AJAX (expects JSON) or submit a form (expects redirect + flash message). Controller should support both.

2) Controller behavior (store method)
- Validate input: ensure `prompt` is present and sanitized.
- Call the service: `$result = $generationService->generate($prompt);` where `$result` is either:
  - string SQL (existing behavior) — in this case the service must be updated to also expose `runId`, OR
  - array/object: `['runId' => 'api_xxx', 'sql' => '...']` (preferred)
- Resolve artifact paths using `$runId` and `storage_path('app/generations/')`.
- If the HTTP request expects JSON (`$request->wantsJson()` or `$request->ajax()`), return `response()->json(['success'=>true,'runId'=>$runId,'links'=>[...]])` with download URLs (e.g., `route('generations.download', ['file' => "{$runId}.sql"])` or `asset('storage/generations/'.$runId.'.sql')` depending on setup).
- Otherwise, redirect back with `session()->flash('status', 'Generation complete'); session()->flash('runId',$runId);`.

3) Security & file access
- Files in `storage/app/generations/` are not publicly accessible by default. Provide controller endpoints to stream files (`download($runId, $type)`) which read from storage and return `download()` response.
- Ensure authentication/authorization for generator and download endpoints.

4) Frontend links
- Example JSON payload returned for AJAX:
  {
    "success": true,
    "runId": "api_xxx",
    "links": {
      "sql": "/generations/download/api_xxx/sql",
      "openapi": "/generations/download/api_xxx/openapi.json",
      "postman": "/generations/download/api_xxx/postman.json"
    }
  }

5) Backward compatibility
- If the service still returns SQL string only, implement a minimal service change: return `['runId' => $runId, 'sql' => $sql]` without breaking callers that expect a string (or add a new method `generateWithId()` and update controller to call it).
