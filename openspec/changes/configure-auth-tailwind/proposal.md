# Proposal: Configure Tailwind and mount `Auth.jsx` in `App.jsx`

## What

Konfigurasi Tailwind untuk menambahkan token warna proyek:
- `dark-bg`: `#1E1E1E`
- `primary-blue`: `#1B3C53`

Tambahkan/ubah `App.jsx` untuk me-mount `Auth.jsx` dan memastikan latar
belakang halaman default adalah `#1E1E1E`. Pastikan frontend membaca env
Vite: `VITE_SUPABASE_URL` dan `VITE_SUPABASE_ANON_KEY`.

## Why

- Menyatukan styling sesuai PRD.
- Menyederhanakan titik masuk React (`App.jsx`) agar komponen frontend
  dapat dikembangkan dan diuji lebih mudah.
- Memastikan variabel environment Supabase tersedia untuk developer.

## Location

Project frontend files under `resources/js/` (or `src/` if applicable).
