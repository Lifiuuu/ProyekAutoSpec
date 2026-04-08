# Tasks for `force-mount-auth`

1. Create change scaffold (this change)

2. [x] Import CSS in `app.js`
   - Add `import '../css/app.css';` (path relative to `resources/js/app.js`) at the very top.

3. [x] Force mount `Auth` in `App.jsx`
    - Update `resources/js/App.jsx` to always render `<Auth />` and add
       `console.log('App is rendering')`.

4. [x] Ensure root ID matches
   - Verify `resources/views/auth.blade.php` or your layout has `<div id="root"></div>`.
   - If it uses a different id, update `resources/js/app.js` to match.

5. [x] Add runtime env checks (optional)
   - Log a warning if `VITE_SUPABASE_URL` or `VITE_SUPABASE_ANON_KEY` are missing.

6. [x] Test locally
   - Start dev servers: `npm run dev` and `php artisan serve`.
   - Visit `http://localhost:8000/auth-test` and confirm console shows "App is rendering",
      background and form appear (not blank). Test passed (visual + console) — 100%.

7. [x] Mark tasks complete in this artifact.
