# Design: `Auth.jsx` (React + Supabase + Tailwind)

## Overview

Komponen `Auth.jsx` adalah form tunggal yang menyediakan dua tindakan: `Login`
dan `Daftar`. Internally it will call the Supabase client methods
`signInWithPassword` and `signUp` from `@supabase/supabase-js`.

## Files to add

- `autospec-frontend/src/components/Auth.jsx` — the React component
- `autospec-frontend/src/lib/supabaseClient.js` — Supabase client initializer (if not present)

## UI / State

- Inputs: `email` (type=email), `password` (type=password)
- Buttons: `Login`, `Daftar`
- Local state: `loading`, `error`, `message`
- On success: show success message and optionally redirect or emit event

## Tailwind & Theme

Use Tailwind utility classes and define color tokens inline using the PRD
palette:

- Background: `#1E1E1E` (use for container background)
- Primary: `#1B3C53` (buttons, accents)
- Text: `#F7F8F0` (primary text)

Example class pattern:

- Container: `bg-[#1E1E1E] text-[#F7F8F0] p-6 rounded-lg` 
- Primary button: `bg-[#1B3C53] hover:bg-opacity-90 text-[#F7F8F0]` 

## Supabase Integration

Create a lightweight client initializer `supabaseClient.js` exporting a
`supabase` instance created with `createClient(SUPABASE_URL, SUPABASE_ANON_KEY)`.

In `Auth.jsx`, call:

- `await supabase.auth.signInWithPassword({ email, password })` for login
- `await supabase.auth.signUp({ email, password })` for signup

Handle errors by catching response errors and setting `error` state.

## Accessibility

- Ensure form elements have labels
- Use aria-live regions for feedback messages
