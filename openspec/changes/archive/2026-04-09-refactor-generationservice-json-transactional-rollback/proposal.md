**What**

Refactor `app/Services/GenerationService.php` to: enforce a strict JSON schema from the LLM, clean LLM output, translate the JSON schema to DDL SQL, and wrap the entire pipeline (SQL execution + OpenAPI generation + Postman collection generation) inside a database transaction so that any failure triggers a rollback.

**Why**

- Current flow risks partial database changes when the LLM output is invalid or subsequent generation steps fail.
- Enforcing a strict JSON schema from the LLM reduces ambiguity and protects DB integrity.
- Transactional behavior ensures Supabase (Postgres) remains consistent on errors.

**Scope**

- Audit and rewrite `app/Services/GenerationService.php` only.
- Add helper functions inside the service: `cleanLlmJson()`, `llmJsonToSql()`, and clear transaction handling.
- Use environment variables: `LLM_API_URL`, `LLM_API_KEY`, `LLM_MODEL`.
- Force Base URL `http://localhost:8000/rest/v1/` in Stage 2 & 3 system prompts.

Location: `openspec/changes/refactor-generationservice-json-transactional-rollback/`
