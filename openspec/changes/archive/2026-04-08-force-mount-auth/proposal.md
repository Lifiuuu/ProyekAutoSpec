# Proposal: Force-mount `Auth` and fix blank page

## What

Beberapa halaman frontend muncul kosong (blank). Usulan ini melakukan langkah
drastis untuk memastikan React benar-benar me-render komponen `Auth` sehingga
UI tidak kosong.

Perubahan yang diusulkan:
- Paksa `App.jsx` untuk selalu merender `<Auth />` tanpa kondisi.
- Pastikan ID root di HTML cocok dengan yang dipanggil di `resources/js/app.js`.
- Import `resources/css/app.css` (Tailwind) di bagian paling atas `app.js`.
- Tambahkan `console.log('App is rendering')` di `App.jsx` untuk debug.

## Why

- Memperbaiki halaman kosong segera dengan tindakan langsung.
- Mempermudah debugging dengan log konsol dan memastikan CSS dipakai.

## Scope

- Edit `resources/js/App.jsx`, `resources/js/app.js`, dan (jika perlu)
  template blade yang berisi elemen root.
- Tidak mengubah logika autentikasi `Auth.jsx` selain mounting.
