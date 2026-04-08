**Implementation Tasks**

1. Update `app/Services/GenerationService.php` (primary task)
   - Add `cleanLlmJson()`, `validateAndDecodeJson()`, `llmJsonToSql()` helpers.
   - Wrap main pipeline in `DB::beginTransaction()` / `try { ... DB::commit(); } catch { DB::rollBack(); }`.
   - Ensure SQL executed via `DB::unprepared()` only after validation.
   - Save three files to `storage/app/generations/{id}.[sql|openapi.json|postman.json]` and only commit if all saved.

2. Add/Update unit tests (where feasible)
   - Add tests for `cleanLlmJson()` with inputs containing markdown fences and noise.
   - Add tests for `llmJsonToSql()` mapping types to allowed SQL types.

3. Manual verification steps
   - Run a generation flow with a small description, verify that invalid LLM JSON triggers rollback and no SQL persisted.
   - Verify valid flow creates SQL and JSON artifacts and DB changes applied.

4. Code review checklist
   - Secrets not logged.
   - All file writes atomic (use temporary files then move/rename).
   - Prompts for stages 2 & 3 include base URL `http://localhost:8000/rest/v1/`.
