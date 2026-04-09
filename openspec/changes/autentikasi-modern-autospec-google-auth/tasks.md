## 1. Auth UI Foundation

- [x] 1.1 Buat struktur halaman `Login` dan `Registrasi` dengan layout card dark mode AutoSpec.
- [x] 1.2 Terapkan token warna branding (`#1E1E1E`, `#141414`, `#234C6A`, `#456882`, `#F7F8F0`) dan soft shadow secara konsisten.
- [x] 1.3 Tampilkan logo AutoSpec di bagian header form login/registrasi dan pastikan layout responsif desktop-mobile.
- [x] 1.4 Tambahkan field Email dan Password beserta state validasi dasar di kedua halaman.

## 2. API Integration and Error UX

- [x] 2.1 Implementasikan service autentikasi di atas `apiClient.js` untuk endpoint login dan registrasi.
- [x] 2.2 Pastikan setiap request auth menyertakan header `Accept: application/json`.
- [x] 2.3 Tambahkan tombol `Sign in with Google` dengan ikon Google pada halaman Login dan hubungkan ke flow auth backend.
- [x] 2.4 Tambahkan toast notification untuk error kredensial salah atau request gagal dengan pesan ramah pengguna.

## 3. Session Management and Redirect

- [x] 3.1 Tambahkan context/hook auth untuk menyimpan token JWT dan metadata sesi.
- [x] 3.2 Persist token ke browser storage agar status login bertahan setelah refresh.
- [x] 3.3 Implementasikan redirect otomatis ke Main Dashboard setelah autentikasi berhasil.
- [x] 3.4 Tambahkan route guard agar pengguna tanpa token valid diarahkan ke halaman Login.

## 4. Validation and Hardening

- [x] 4.1 Uji flow end-to-end registrasi -> login -> redirect dashboard -> refresh tetap login.
- [x] 4.2 Uji flow Google Sign-In sampai pengguna kembali ke dashboard dalam keadaan terautentikasi.
- [x] 4.3 Verifikasi semua error utama (401/422/500) menampilkan toast yang benar tanpa merusak input form.
- [x] 4.4 Pastikan implementasi auth tidak mengganggu fitur generator skema dan history dashboard yang sudah ada.