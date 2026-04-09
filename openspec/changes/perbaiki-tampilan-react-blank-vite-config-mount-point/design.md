# Design

- Add `@viteReactRefresh` directive above `@vite()` in the Blade view so Vite React Fast Refresh works during development.
- Update `@vite` to reference `resources/js/app.jsx` (entry file that includes JSX and mounts React).
- Ensure the HTML mount element uses `id="app"` and create `resources/js/app.jsx` that mounts React to `#app`.

This keeps changes minimal and focused on syncing entrypoint and mount ID. No changes to existing components required.
