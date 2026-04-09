**Implementation Tasks**

1. Audit `GeneratorController.php` (or equivalent controller)
   - Locate the method that receives dashboard form submissions (likely `store()` or `generate()`).
   - Ensure it validates the `prompt` input.

2. Update `GenerationService` to return `runId`
   - Prefer changing `generate()` to return `['runId' => $runId, 'sql' => $sql]`.
   - Alternatively expose `getLastRunId()` if keeping current return type.

3. Update `GeneratorController` store method
   - Call `$result = $generationService->generate($request->prompt);`
   - Extract `$runId = is_array($result) ? $result['runId'] : $generationService->getLastRunId();`
   - Build artifact URLs/route names for SQL, OpenAPI, Postman.
   - Return JSON when `wantsJson()` otherwise redirect back with flash.

4. Add download endpoint(s)
   - Add `GET /generations/download/{runId}/{type}` that streams files from `storage/app/generations/{runId}.{ext}`.
   - Route names: `generations.download`.
   - Protect routes with middleware `auth` as needed.

5. Frontend integration notes
   - Dashboard should call endpoint via AJAX and read `links` to render downloads.
   - Or submit form and read flash `runId` to show links after redirect.

6. Tests & manual verification
   - Unit test controller behavior for both AJAX and form submits (mock `GenerationService`).
   - Manual test: run generation from dashboard, verify files exist in `storage/app/generations/` and downloads work.
