# Tasks

1. [x] Update `Dashboard.jsx` to import `axios` (or use existing `apiClient`) and replace the `handleGenerate` function:
   - Set `isLoading` true before request and false in `finally`.
   - POST to `/api/generate` with payload `{ prompt: nlpPrompt, dialect }`.
   - On success map response to `generatedSql`, `schemaOverview`, and toggle review/schema panels.
   - On error show user feedback (`alert` or `showRollbackToast`) and log error.
2. Run dev server (`npm run dev`) and test the Generate button with a real prompt.
3. If backend route differs, update endpoint path accordingly.
