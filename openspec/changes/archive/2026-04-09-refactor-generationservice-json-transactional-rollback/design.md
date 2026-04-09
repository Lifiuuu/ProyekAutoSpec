**Design**

Goal: Implement deterministic, safe generation pipeline in `GenerationService`.

1) System Prompt (Stage 1) — EXACT TEXT (strict):

"""
Kamu adalah Database Schema Generator yang akurat. Ubah deskripsi user menjadi JSON murni.
ATURAN KETAT:
1. Output HARUS valid JSON.
2. Struktur JSON wajib seperti ini:
{
    "tables": [
        {
            "name": "nama_tabel",
            "columns": [
                {"type": "id", "name": "id"},
                {"type": "integer", "name": "barang_id"},
                {"type": "string", "name": "nama"}
            ],
            "dummy_data": [ {"nama": "Buku"} ]
        }
    ],
    "stored_procedures": [
        { "name": "nama_sp", "definition": "CREATE OR REPLACE PROCEDURE..." }
    ],
    "functions": [
        { "name": "nama_fungsi", "definition": "CREATE OR REPLACE FUNCTION..." }
    ],
    "triggers": [
        { "name": "nama_trigger", "definition": "CREATE TRIGGER..." }
    ]
}
3. PENTING UNTUK TYPE: Hanya gunakan: "id", "string", "integer", "text", "boolean", "date", "datetime", "decimal". Jangan gunakan SQL asli seperti VARCHAR atau INT!
4. Gunakan "id" HANYA untuk Primary Key bernama "id". Untuk Foreign Key (seperti user_id), WAJIB gunakan "integer".
5. TRIGGER di PostgreSQL HANYA BOLEH memanggil FUNCTION, bukan PROCEDURE!
"""

2) Transaction Flow

- Begin with `DB::beginTransaction()`.
- Steps inside transaction:
  1. Call LLM Stage 1, clean output via `cleanLlmJson()`.
  2. `json_decode()` with strict checks; if invalid, throw and `DB::rollBack()`.
  3. Convert JSON -> DDL via `llmJsonToSql()` producing SQL string(s).
  4. Execute SQL using `DB::unprepared()`.
  5. Call LLM Stage 2 (OpenAPI) and Stage 3 (Postman) forcing base URL `http://localhost:8000/rest/v1/` in those system prompts.
  6. Save generated files `storage/app/generations/{id}.sql`, `storage/app/generations/{id}.openapi.json`, `storage/app/generations/{id}.postman.json`.
- If any step throws, `DB::rollBack()` and delete any partial files; return the error.
- Only call `DB::commit()` after all three files exist and SQL executed successfully.

3) Helpers (implemented as private methods in the service)

- `private function cleanLlmJson(string $raw): string` — strip markdown fences, leading/trailing text, and common noise; return sanitized JSON string.
- `private function validateAndDecodeJson(string $json): array` — `json_decode` with error handling and schema checks (presence of `tables` key, types allowed).
- `private function llmJsonToSql(array $schema): string` — walk the schema and produce DDL using allowed types mapping (id => SERIAL PRIMARY KEY, integer => INTEGER, string => TEXT, etc.), create tables, constraints, functions, triggers (ensuring triggers call functions). Return concatenated SQL.

4) LLM configuration

- Use env vars: `env('LLM_API_URL')`, `env('LLM_API_KEY')`, `env('LLM_MODEL')`.
- Ensure Stage 2 & 3 system prompts include Base URL: `http://localhost:8000/rest/v1/`.

5) Error handling & logging

- On any exception, roll back DB and log the error with context (raw LLM output and cleaned JSON) to the application log but avoid leaking secrets.
