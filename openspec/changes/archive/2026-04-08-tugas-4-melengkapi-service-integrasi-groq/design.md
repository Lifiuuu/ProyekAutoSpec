# Design: TUGAS 4 — Groq integration and artifact generation

Add two Groq API calls inside `GenerationService::generate()` after executing the SQL:

- Groq Call 1 (OpenAPI):
  - System prompt: enforce output as pure OpenAPI 3.0.0 JSON.
  - Send the generated SQL as user content.
  - Validate response is valid JSON; save to `storage/app/openapi.json`.

- Groq Call 2 (Postman):
  - System prompt: enforce output as pure Postman Collection v2.1.0 JSON and ensure Supabase header examples include `apikey` and `Authorization: Bearer <SUPABASE_KEY>`.
  - Send the generated SQL as user content.
  - Validate response is valid JSON; save to `storage/app/postman_collection.json`.

Implementation notes:
- Use `GROQ_API_KEY` from `.env` and `GROQ_API_URL` (fallback to `LLM_API_URL` if needed).
- Use Laravel `Http` facade for Groq calls and strict `try/catch` with logging.
- If JSON parsing fails, throw an exception with details from `json_last_error_msg()` and the raw response for debugging.
