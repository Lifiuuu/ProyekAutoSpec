## Context

AutoSpec saat ini sudah memiliki Main Dashboard generator, tetapi belum memiliki pintu masuk autentikasi yang aman untuk memisahkan konteks pengguna dan melindungi operasi backend. Implementasi perlu mencakup dua layar (Login dan Registrasi), login Google, dan persistensi sesi agar pengalaman pengguna tetap mulus pada refresh halaman.

Kendala utama:
- Frontend berjalan di React + Tailwind dengan gaya visual Semute (dark palette).
- Integrasi API harus melalui `apiClient.js` dan konsisten menggunakan header `Accept: application/json`.
- Hasil autentikasi perlu disimpan aman di browser dan dipakai untuk redirect ke dashboard.
- UX harus terasa seperti SaaS modern: cepat, jelas, dan tidak membingungkan saat error.

## Goals / Non-Goals

**Goals:**
- Menyediakan halaman Login dan Registrasi yang konsisten dengan branding AutoSpec.
- Menyediakan login berbasis email/password dan tombol `Sign in with Google`.
- Menyediakan redirect otomatis ke Main Dashboard setelah autentikasi berhasil.
- Menyediakan toast error yang ramah saat kredensial salah atau API gagal.
- Menyimpan token autentikasi agar sesi tetap aktif setelah refresh.

**Non-Goals:**
- Tidak merombak arsitektur backend autentikasi Laravel secara menyeluruh.
- Tidak membangun manajemen role/permission granular pada perubahan ini.
- Tidak mengganti desain dashboard utama selain kebutuhan guard dan redirect.

## Decisions

1. Gunakan `AuthLayout` + komponen form terpisah untuk Login/Registrasi
- Rationale: konsistensi visual dan reusable UI.
- Alternative: satu halaman auth dengan mode toggle. Ditolak karena memperumit validasi UX awal.

2. Simpan token sesi pada storage browser + state context ringan
- Rationale: token tetap tersedia saat refresh sekaligus mudah dipakai oleh komponen React.
- Alternative: in-memory state only. Ditolak karena sesi hilang saat reload.

3. Integrasikan seluruh request autentikasi lewat `apiClient.js`
- Rationale: memastikan kontrak request seragam, termasuk `Accept: application/json`.
- Alternative: memanggil `fetch` langsung di komponen. Ditolak karena duplikasi logic error handling.

4. Gunakan toast untuk error autentikasi dan feedback pengguna
- Rationale: error terlihat jelas tanpa memecah flow form.
- Alternative: alert native browser. Ditolak karena UX kurang profesional.

5. Redirect sukses login ke Main Dashboard dengan guard route sederhana
- Rationale: memenuhi kebutuhan UX “langsung kerja” setelah login.
- Alternative: landing perantara. Ditolak karena menambah langkah tidak perlu.

## Risks / Trade-offs

- [Token tersimpan di browser] -> Mitigation: batasi data yang disimpan (token + metadata minimum), validasi expiry, dan siapkan jalur logout bersih.
- [Flow Google Auth berbeda antar environment] -> Mitigation: abstrah endpoint callback di `apiClient.js` dan gunakan konfigurasi env yang eksplisit.
- [API response auth bisa tidak konsisten] -> Mitigation: normalisasi shape response di helper auth service sebelum dipakai UI.
- [Toast berlebihan menurunkan UX] -> Mitigation: tampilkan hanya error kritis, debounce pesan identik.

## Migration Plan

1. Tambahkan struktur halaman auth: Login dan Registrasi dengan style token warna AutoSpec.
2. Tambahkan service auth di atas `apiClient.js` untuk login, registrasi, dan Google auth.
3. Tambahkan context/hook auth untuk menyimpan token dan status login persisten.
4. Terapkan route guard: user belum login ke auth page, user login ke dashboard.
5. Tambahkan toast handling untuk error API.
6. Uji end-to-end: register -> login -> redirect dashboard -> refresh -> sesi tetap aktif.

Rollback:
- Nonaktifkan route guard baru dan kembalikan entry point langsung ke dashboard lama.
- Hapus pemakaian context auth tanpa mengubah endpoint backend.

## Open Questions

- Endpoint final untuk Google Sign-In menggunakan redirect penuh (OAuth callback) atau token exchange langsung?
- Apakah registrasi perlu verifikasi email pada fase ini atau ditunda ke fase berikutnya?
- Di mana lokasi terbaik untuk menaruh refresh token jika backend nanti mendukung rotasi token?