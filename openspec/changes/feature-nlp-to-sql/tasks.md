## 1. Supabase Edge Function Scaffold

- [x] 1.1 Buat folder `supabase/functions/nlp-to-sql/` untuk fungsi baru.
- [x] 1.2 Tambahkan handler Edge Function yang menerima request JSON berisi prompt natural language.
- [x] 1.3 Implementasikan validasi Authorization header dari `apiClient`.
- [x] 1.4 Kembalikan response JSON terstruktur dari hasil eksekusi query.

## 2. Local Testing Setup

- [x] 2.1 Siapkan data contoh dan payload request untuk pengujian lokal Edge Function.
- [x] 2.2 Jalankan pengujian lokal fungsi Supabase untuk memverifikasi request dan response JSON.
- [x] 2.3 Verifikasi skenario gagal autentikasi dan payload tidak valid menghasilkan error JSON yang konsisten.

## 3. React Integration

- [x] 3.1 Hubungkan komponen React ke endpoint Edge Function melalui `apiClient` yang sudah ada.
- [x] 3.2 Kirim prompt NLP dan header Authorization dari frontend saat generate dijalankan.
- [x] 3.3 Render hasil JSON dari Edge Function ke UI tanpa bergantung pada raw SQL.

## 4. Verification and Cleanup

- [x] 4.1 Uji alur end-to-end dari input prompt sampai hasil JSON tampil di frontend.
- [x] 4.2 Pastikan alur error tidak menghapus state UI yang valid secara tidak sengaja.
- [x] 4.3 Jalankan build atau test frontend untuk memastikan integrasi tidak memunculkan error kompilasi.