# Perbaiki Tampilan React Blank (Vite Config & Mount Point)

## What
Memperbaiki masalah tampilan React yang menghasilkan layar putih setelah merge frontend (React) ke backend (Laravel) dengan men-sinkronkan entry point Vite dan mount point React.

## Why
Setelah merge, Blade masih mereferensikan `resources/js/app.js` dan mount point HTML menggunakan `id="root"`, sedangkan setup React modern dan Vite memerlukan entry file dengan ekstensi `.jsx` (untuk JSX) dan konsistensi ID mount. Perubahan ini memastikan Hot Refresh bekerja dan React dapat ter-mount dengan benar.

Change path: `resources/views/main-dashboard.blade.php` and add `resources/js/app.jsx`.
