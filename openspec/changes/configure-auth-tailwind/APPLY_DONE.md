Change `configure-auth-tailwind` applied

Summary of work applied:

- Added Tailwind config `tailwind.config.js` with color tokens:
  - `dark-bg`: `#1E1E1E`
  - `primary-blue`: `#1B3C53`
  - `as-text`: `#F7F8F0`
- Created `resources/js/App.jsx` that wraps `Auth` and emits a console
  warning when `VITE_SUPABASE_URL` or `VITE_SUPABASE_ANON_KEY` are missing.
- Updated `resources/js/app.js` to mount `App` at `#root` and keep
  `#auth-root` for compatibility with the test page.
- Confirmed `resources/js/lib/supabaseClient.js` reads `import.meta.env`.
- Updated `openspec` artifacts: marked tasks complete and set change as applied.

Manual verification steps performed by developer:
- Started Vite dev server (`npm run dev`) and observed reloads after edits.

Manual verification to complete by tester:
- Visit Laravel route `http://localhost:8000/auth-test` (or `http://localhost/auth-test`) and verify:
  - Page background uses `#1E1E1E` (dark-bg token applied).
  - Login form renders and is styled with tokens (primary button `#1B3C53`, text `#F7F8F0`).
  - No console warnings appear when `VITE_SUPABASE_URL` and `VITE_SUPABASE_ANON_KEY` are set.

If you want this change archived, run `/opsx:archive configure-auth-tailwind`.
