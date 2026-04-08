## 1. Data Mapping for Visual Schema

- [x] 1.1 Definisikan mapper untuk mengubah payload JSON API menjadi struktur tabel yang siap dirender.
- [x] 1.2 Pastikan mapper mengekstrak Nama Tabel, daftar kolom, dan tipe data utama (ID, String, Integer).
- [x] 1.3 Tambahkan fallback data aman saat payload kosong atau sebagian field tidak tersedia.

## 2. Visual Schema Overview UI

- [x] 2.1 Buat komponen Visual Schema Overview di area konten utama dashboard.
- [x] 2.2 Render kartu per tabel dengan heading Nama Tabel dan daftar kolom.
- [x] 2.3 Tampilkan badge/label tipe data (ID, String, Integer) di setiap item kolom yang relevan.
- [x] 2.4 Pastikan layout kartu tetap terbaca pada desktop dan mobile.

## 3. Credentials and Download Cards

- [x] 3.1 Tambahkan kartu Database Credentials terpisah dari kartu tabel.
- [x] 3.2 Tampilkan username dan password unik pada kartu credentials dengan gaya visual yang jelas.
- [x] 3.3 Tambahkan tiga tombol unduh untuk `database.sql`, `openapi.json`, dan `postman_collection.json`.
- [x] 3.4 Hubungkan setiap tombol ke target file yang sesuai serta sediakan disabled state bila file belum tersedia.

## 4. Integration and Validation

- [x] 4.1 Integrasikan section Visual Schema Overview, Credentials, dan Download Actions ke flow MainDashboard tanpa merusak struktur Sidebar.
- [x] 4.2 Verifikasi manual bahwa data JSON berhasil dipetakan menjadi kartu tabel sesuai requirement.
- [x] 4.3 Verifikasi manual bahwa tombol unduh tampil lengkap dan masing-masing memetakan file yang benar.
- [x] 4.4 Jalankan build frontend untuk memastikan perubahan UI lolos kompilasi.
