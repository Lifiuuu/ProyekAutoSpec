# Proposal: Add `Auth.jsx` component

## What

Buat komponen React `Auth.jsx` untuk sistem login dan pendaftaran (email + password)
menggunakan `@supabase/supabase-js`. Komponen ini akan dipasang di frontend AutoSpec
(`autospec-frontend`) dan meng-handle alur autentikasi dasar.

## Why

- Mempercepat integrasi autentikasi user pada aplikasi.
- Gunakan Supabase untuk backend auth agar sederhana dan terkelola.
- Consistent UI dengan palet warna PRD (aksesibilitas dan konsistensi brand).

## Scope

- Form Email + Password
- Tombol `Login` dan `Daftar` yang memanggil supabase.auth.signInWithPassword
  dan supabase.auth.signUp
- Feedback untuk sukses / gagal
- Styling dengan Tailwind CSS menggunakan palet PRD: background `#1E1E1E`,
  primary `#1B3C53`, teks `#F7F8F0`.

## Out of scope

- Social login (OAuth) dan MFA
- Full user profile management

## Location

Create artifacts under `openspec/changes/add-auth-component/`.
