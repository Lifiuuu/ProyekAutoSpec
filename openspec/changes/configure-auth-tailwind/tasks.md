# Tasks for `configure-auth-tailwind`

1. Create change scaffold
   - `openspec/changes/configure-auth-tailwind/.openspec.yaml` (done)

2. [x] Update Tailwind config
   - File: `tailwind.config.js` added with `dark-bg`, `primary-blue`, `as-text`.

3. [x] Create `App.jsx` and mount `Auth`
   - File: `resources/js/App.jsx` created and wraps `Auth`.
   - `resources/js/app.js` updated to mount `App` at `#root` and keep `#auth-root` for compatibility.

4. [x] Verify Vite env reading
   - `resources/js/lib/supabaseClient.js` uses `import.meta.env`.
   - `App.jsx` includes a runtime console warning if env vars are missing.

5. [~] Test locally
   - Dev server (`npm run dev`) and Laravel (`php artisan serve`) should be running.
   - Visit `http://localhost/auth-test` or Vite root to confirm component mounts and styles apply.

6. [x] Update artifacts and mark done
   - All implementation tasks completed and change marked applied.
