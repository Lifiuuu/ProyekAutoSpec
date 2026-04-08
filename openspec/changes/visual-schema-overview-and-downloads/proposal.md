## Why

Pengguna membutuhkan representasi visual dari hasil JSON API agar struktur database lebih mudah dipahami sebelum proses lanjutan. Selain itu, akses cepat ke kredensial database dan file hasil generate diperlukan untuk mempercepat workflow review dan handoff.

## What Changes

- Menambahkan komponen Visual Schema Overview untuk memetakan JSON API menjadi kartu-kartu tabel.
- Setiap kartu tabel menampilkan Nama Tabel, Daftar Kolom, dan tipe data utama seperti ID, String, Integer.
- Menambahkan kartu Database Credentials yang menampilkan username dan password unik.
- Menambahkan tiga tombol unduh pada bagian bawah untuk file `database.sql`, `openapi.json`, dan `postman_collection.json`.
- Menyusun tampilan agar tetap terbaca pada desktop dan mobile.

## Capabilities

### New Capabilities
- `visual-schema-overview`: Menyediakan visualisasi skema database berbasis kartu dari payload JSON API.
- `artifact-download-actions`: Menyediakan aksi unduh untuk file SQL, OpenAPI, dan Postman collection dari antarmuka.

### Modified Capabilities
- `main-dashboard-generator-layout`: Menambahkan section overview skema, kartu kredensial, dan aksi unduh di alur dashboard utama.

## Impact

- Affected code: komponen dashboard utama, renderer data JSON ke UI card, dan handler tombol unduh.
- API impact: Tidak mengubah kontrak API; hanya memanfaatkan payload hasil API yang sudah ada.
- Dependencies: Tidak wajib menambah dependency eksternal baru.
- UX impact: Pengguna dapat meninjau struktur skema dan mengambil artifact penting dalam satu layar.
