# Design: Force mount, root ID check, CSS import, debug log

1) Force mount

- Update `resources/js/App.jsx` to unconditionally render `<Auth />` at top
  level and include `console.log('App is rendering')` so the browser console
  confirms rendering.

2) Check root ID

- Ensure the HTML view used for testing (e.g., `resources/views/auth.blade.php`,
  or the app layout) contains a root element with an id that matches the mount
  point in `resources/js/app.js`. Prefer `id="root"` or `id="app"`.
- Update `resources/js/app.js` to use the same id (`document.getElementById('root')`).

3) Fix CSS import

- Ensure `resources/css/app.css` (which contains `@tailwind` directives) is
  imported at the very top of `resources/js/app.js` so styles are bundled and
  applied before components render.

4) Runtime debugging

- Add a small runtime check in `App.jsx` that logs a warning if
  `import.meta.env.VITE_SUPABASE_URL` or `VITE_SUPABASE_ANON_KEY` are missing.

Files touched (examples):

- `resources/js/app.js` — import CSS at top, mount `App` to `#root`.
- `resources/js/App.jsx` — force render `<Auth />`, add `console.log`.
- `resources/views/auth.blade.php` or layout — ensure `<div id="root"></div>` exists.
