## Why

AutoSpec sudah bisa menghasilkan skema database dan artefak API, tetapi pengguna belum memiliki viewer dokumentasi interaktif yang mudah dibuka dari dashboard. Perubahan ini diperlukan agar hasil generasi bisa langsung divalidasi lewat Swagger UI, sehingga demo CRUD dan sinkronisasi dengan skema database terlihat lebih jelas dan cepat.

## What Changes

- Menambahkan komponen React `SwaggerDocs.jsx` untuk merender `openapi.json` secara interaktif.
- Menyediakan container dokumentasi dalam modal atau full-width card agar menyatu dengan dashboard.
- Menerapkan tema dark mode AutoSpec dengan palet `#1E1E1E`, `#F7F8F0`, dan aksen `#456882`.
- Menyembunyikan elemen Swagger yang tidak diperlukan, termasuk search bar bawaan, agar UI tetap minimalis.
- Mempertahankan fitur `Try it out` untuk pengujian endpoint CRUD ke backend PostgreSQL di Kubernetes.
- Menampilkan ringkasan schema/model database di bawah dokumentasi untuk validasi tipe data.
- Menambahkan tombol akses cepat `Lihat Dokumentasi API` pada sidebar atau area hasil generasi.

## Capabilities

### New Capabilities
- `swagger-api-documentation-panel`: Menyediakan viewer Swagger UI interaktif untuk `openapi.json`, termasuk `Try it out`, tema dark, dan ringkasan model database.

### Modified Capabilities
- `main-dashboard-generator-layout`: Menambahkan entry point dan area UI untuk membuka dokumentasi API dari dashboard generasi utama.
- `sidebar-history-navigation`: Menambahkan tombol akses cepat `Lihat Dokumentasi API` di sidebar atau area hasil agar dokumentasi mudah dibuka dari konteks yang sama.

## Impact

- Affected code: komponen React dashboard, sidebar, dan komponen baru `SwaggerDocs.jsx`.
- Dependencies: menambah `swagger-ui-react` beserta styling pendukungnya.
- Data flow: dashboard perlu menerima `specData` dari `openapi.json` dan meneruskannya ke viewer Swagger.
- UX impact: pengguna dapat membuka dokumentasi, menguji endpoint, dan memeriksa model database tanpa meninggalkan dashboard.