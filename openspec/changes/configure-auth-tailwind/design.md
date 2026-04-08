# Design: Tailwind tokens, `App.jsx`, and Vite env verification

## Tailwind

- Update `tailwind.config.js` to add custom colors:

```js
module.exports = {
  theme: {
    extend: {
      colors: {
        'dark-bg': '#1E1E1E',
        'primary-blue': '#1B3C53',
        'as-text': '#F7F8F0',
      },
    },
  },
}
```

Use these tokens in component classes (e.g. `bg-dark-bg`, `bg-primary-blue`, `text-as-text`).

## App.jsx

- Create `resources/js/App.jsx` (or `src/App.jsx`) that:
  - Imports React and `Auth` component (`resources/js/components/Auth.jsx`).
  - Wraps `Auth` in a container with `bg-dark-bg min-h-screen text-as-text`.
  - Exports default `App` component.

- Update `resources/js/app.js` to mount `App` when an element with id
  `root` exists (or keep `#auth-root` for backwards compatibility).

## Vite envs

- Verify `supabaseClient` uses `import.meta.env.VITE_SUPABASE_URL` and
  `import.meta.env.VITE_SUPABASE_ANON_KEY` (already implemented). Add a
  small runtime check in `App.jsx` that logs a console warning if missing.

## Accessibility & Notes

- Keep form labels and `aria-live` regions for feedback. No design changes
  to Auth logic are planned here — just mounting and styling.
