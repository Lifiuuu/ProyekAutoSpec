# Tasks for `add-auth-component`

1. [x] Create change scaffold
   - Ensure `openspec/changes/add-auth-component/.openspec.yaml` exists.

2. [x] Add Supabase client (if not present)
   - File: `src/lib/supabaseClient.js`
   - Export initialized `supabase` client using env variables.

3. [x] Implement `Auth.jsx` component
   - File: `src/components/Auth.jsx`
   - Implement email/password inputs, `Login` and `Daftar` buttons.
   - Hook up `supabase.auth.signInWithPassword` and `supabase.auth.signUp`.
   - Provide loading and success/error feedback.
   - Style with Tailwind using PRD palette.

4. [x] Wire into app
   - `resources/views/auth.blade.php` and route `/auth-test` added.

5. [x] Test flows
   - Manual testing steps completed and verified the dev server runs.
   - Note: full signup/login requires valid Supabase env vars (`VITE_SUPABASE_URL`, `VITE_SUPABASE_ANON_KEY`) set in your environment.

6. [x] Documentation
   - README updated with env and test instructions.

7. [x] Ready for implementation
   - All tasks complete. Change is ready for archive or apply.
