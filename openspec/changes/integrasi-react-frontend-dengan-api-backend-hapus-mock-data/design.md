# Design

- Replace the current client-side mock generation (`fakeAiGenerate`) usage inside `handleGenerate` with an HTTP POST to the backend endpoint `/api/generate`.
- Use `axios` (already present in `package.json`) to send JSON payload `{ prompt: nlpPrompt, dialect }`.
- Manage loading state: set `isLoading` true before request and false in `finally`.
- On success map response fields to component state:
  - `generatedSql` (ddl,dml,dcl,trigger)
  - `schemaOverview.tables` (use `mapSchemaJsonToTables` on `schema_overview`)
  - `schemaOverview.credentials`, `downloads`, `files` if provided
  - show review and schema panels
- On error: capture and show a message (alert or `showRollbackToast`) and log details to console.

No backend code changes are assumed; the frontend will POST to `/api/generate` and expect JSON response with keys like `runId`, `sql_ddl`, `sql_dml`, `schema_overview`, `credentials`, `downloads`, `files`.
