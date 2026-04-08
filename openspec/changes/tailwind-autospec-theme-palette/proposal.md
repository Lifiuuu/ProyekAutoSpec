## Why

Saat ini tema Tailwind belum memiliki definisi palet AutoSpec yang konsisten sehingga penggunaan warna pada komponen berisiko tidak seragam. Perubahan ini diperlukan sekarang agar tim bisa memakai token warna yang sama melalui class utilitas Tailwind tanpa hardcode nilai hex berulang.

## What Changes

- Menambahkan palet warna AutoSpec pada konfigurasi Tailwind: `background`, `primary`, `secondary`, `accent`, dan `text`.
- Menyediakan variabel CSS untuk setiap token warna agar dapat dipakai konsisten lintas komponen.
- Memetakan warna Tailwind ke variabel CSS sehingga class seperti `bg-primary`, `text-accent`, dan `bg-background` dapat digunakan secara langsung.
- Menetapkan pola implementasi agar tetap kompatibel dengan alur build Vite + Tailwind pada proyek.

## Capabilities

### New Capabilities
- `tailwind-theme-palette`: Menyediakan token tema AutoSpec berbasis CSS variable dan mapping warna Tailwind untuk pemakaian utilitas class yang konsisten.

### Modified Capabilities
- None.

## Impact

- Affected code: `tailwind.config.js` dan stylesheet global (`resources/css/app.css` atau file CSS entry Tailwind yang aktif).
- API impact: Tidak ada perubahan API backend.
- Dependencies: Tidak ada dependency runtime baru; hanya konfigurasi dan styling.
- Developer workflow: Pengembang dapat menggunakan class utilitas warna standar (`bg-primary`, `text-accent`, dsb.) secara konsisten.
