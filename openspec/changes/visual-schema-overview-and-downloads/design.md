## Context

Dashboard generator saat ini berfokus pada input prompt, proses generate, dan review SQL. Pengguna masih perlu langkah tambahan untuk memahami struktur skema dari payload JSON API dan mengambil artifact hasil generate. Fitur Visual Schema Overview menambahkan representasi tabel berbasis kartu, kartu kredensial database, serta tombol unduh artifact agar alur review lebih cepat dan terpusat.

## Goals / Non-Goals

**Goals:**
- Memetakan hasil JSON API menjadi kartu-kartu tabel yang mudah dipindai.
- Menampilkan Nama Tabel, daftar kolom, dan tipe data penting (ID, String, Integer) dalam tiap kartu.
- Menampilkan kartu Database Credentials berisi username dan password unik.
- Menyediakan tiga tombol unduh untuk `database.sql`, `openapi.json`, dan `postman_collection.json`.
- Menjaga tampilan tetap responsif pada desktop dan mobile.

**Non-Goals:**
- Mengubah format payload API sumber.
- Menjalankan validasi keamanan kredensial di backend pada fase ini.
- Menambahkan fitur edit schema langsung dari kartu overview.

## Decisions

1. Gunakan mapper JSON terpusat sebelum rendering UI cards.
- Rationale: Menjaga pemisahan data transformation dan presentasi komponen.
- Alternative considered: Parsing langsung di setiap elemen UI.
- Why not: Sulit dirawat saat struktur JSON berubah.

2. Render schema sebagai kumpulan kartu tabel dengan heading dan metadata ringkas.
- Rationale: Mempercepat scanning banyak tabel dibanding satu blok panjang.
- Alternative considered: Tabel HTML tunggal.
- Why not: Kurang fleksibel pada layar kecil dan kurang modular.

3. Tampilkan Database Credentials pada kartu terpisah dengan visual emphasis.
- Rationale: Memudahkan pengguna menemukan informasi koneksi tanpa bercampur dengan metadata tabel.
- Alternative considered: Menyisipkan kredensial di atas daftar tabel.
- Why not: Mengurangi fokus dan memperumit hirarki visual.

4. Implement tombol unduh sebagai action terpisah per file.
- Rationale: Memberi kontrol eksplisit terhadap artifact yang dibutuhkan user.
- Alternative considered: Satu tombol unduh semua file.
- Why not: Tidak granular dan berpotensi membebani pengguna yang hanya butuh sebagian file.

## Risks / Trade-offs

- [Struktur JSON API bervariasi antar response] -> Mitigasi: Tambahkan normalisasi dan fallback empty state pada mapper.
- [Eksposur password sensitif di UI] -> Mitigasi: Gunakan mode reveal/obfuscation opsional dan batasi scope tampilan hanya untuk user berizin.
- [Link unduh rusak bila artifact belum tersedia] -> Mitigasi: Tambahkan validasi ketersediaan file dan disabled state pada tombol terkait.
