## Why

AutoSpec belum memiliki lapisan autentikasi modern untuk melindungi akses ke proses generate skema database dan artefak backend. Perubahan ini diperlukan sekarang agar keamanan platform meningkat, onboarding pengguna lebih cepat lewat Google Sign-In, dan setiap hasil generate dapat diikat ke identitas akun secara konsisten.

## What Changes

- Menambahkan alur autentikasi frontend dengan dua halaman: Login dan Registrasi berbasis React + Tailwind dengan branding AutoSpec.
- Menambahkan tombol `Sign in with Google` pada halaman login dan menghubungkannya ke endpoint autentikasi backend.
- Menghubungkan form login/registrasi ke `apiClient.js` dengan request `POST` dan header `Accept: application/json`.
- Menambahkan penanganan error berbasis toast untuk kredensial salah atau kegagalan autentikasi lainnya.
- Menambahkan penyimpanan token autentikasi (JWT) agar status login bertahan saat refresh.
- Menambahkan redirection otomatis ke Main Dashboard setelah login berhasil.

## Capabilities

### New Capabilities
- `autospec-auth-ui-and-branding`: Menyediakan halaman Login dan Registrasi dengan visual branding AutoSpec yang konsisten dan responsif.
- `autospec-auth-api-integration`: Menyediakan integrasi request autentikasi (login, registrasi, Google Sign-In) melalui `apiClient.js` dengan kontrak request/response yang konsisten.
- `autospec-auth-session-and-redirect`: Menyediakan manajemen token, persistensi sesi login, proteksi akses dasar, dan redirection ke Main Dashboard setelah autentikasi sukses.

### Modified Capabilities
- Tidak ada.

## Impact

- Affected code:
  - Frontend auth pages/components baru untuk Login dan Registrasi.
  - Integrasi request auth di layer `apiClient.js`.
  - Guard dan state autentikasi pada root/layout React.
  - Utility toast/notifikasi error pada alur auth.
- APIs:
  - Endpoint autentikasi login/registrasi/Google akan dipanggil dari frontend.
- Dependencies:
  - Potensi penambahan paket UI auth (ikon Google atau helper toast) jika belum tersedia.
- Systems:
  - Alur sesi pengguna dan isolasi data per-akun menjadi lebih kuat untuk kebutuhan Multi-Schema Isolation.