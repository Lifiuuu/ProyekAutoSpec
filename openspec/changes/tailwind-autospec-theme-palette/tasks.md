## 1. Theme Token Setup

- [x] 1.1 Identifikasi file stylesheet global yang menjadi entry CSS aktif untuk frontend.
- [x] 1.2 Tambahkan variabel CSS global `--background`, `--primary`, `--secondary`, `--accent`, dan `--text` sesuai palet AutoSpec.
- [x] 1.3 Pastikan format nilai variabel kompatibel untuk konsumsi oleh mapping warna Tailwind dan dukungan opacity utility.

## 2. Tailwind Configuration

- [x] 2.1 Perbarui `tailwind.config.js` untuk memetakan warna `background`, `primary`, `secondary`, `accent`, dan `text` ke CSS variables.
- [x] 2.2 Terapkan pola mapping yang mendukung modifier opacity pada utilitas seperti `bg-primary/80`.
- [x] 2.3 Pastikan perubahan di-merge pada `theme.extend.colors` tanpa merusak konfigurasi warna yang sudah ada.

## 3. Verification

- [x] 3.1 Gunakan class contoh (`bg-primary`, `text-accent`, `bg-background`) pada view/komponen untuk memverifikasi output CSS.
- [x] 3.2 Jalankan build frontend untuk memastikan konfigurasi Tailwind valid dan tidak menimbulkan error.
- [x] 3.3 Dokumentasikan cara pakai token warna AutoSpec secara singkat untuk referensi tim.
