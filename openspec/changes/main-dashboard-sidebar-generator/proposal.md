## Why

Halaman dashboard utama yang berfokus pada alur generasi query/database belum tersedia, sehingga pengalaman pengguna belum terstruktur untuk memulai prompt NLP dan memilih target dialect secara jelas. Perubahan ini diperlukan sekarang agar fondasi UI generator AutoSpec siap dipakai dengan komponen yang konsisten dan siap dikembangkan lanjut.

## What Changes

- Menambahkan komponen MainDashboard dengan layout dua kolom: Sidebar di kiri dan konten utama di kanan.
- Menambahkan Sidebar berisi logo AutoSpec di bagian atas dan daftar Histori Generasi di bawahnya.
- Menambahkan NLP Prompt Box berupa textarea besar dengan border warna #234C6A dan placeholder Bikinin database perpustakaan....
- Menambahkan dropdown target database dengan opsi PostgreSQL sebagai opsi aktif, serta MySQL, MariaDB, dan SQLite dalam status disabled dengan label Coming Soon.
- Menambahkan tombol Generate berwarna #456882 yang memicu handler onGenerate saat diklik.

## Capabilities

### New Capabilities
- `main-dashboard-generator-layout`: Menyediakan antarmuka MainDashboard dengan Sidebar histori, input prompt NLP, pemilihan dialect database, dan aksi generate.

### Modified Capabilities
- None.

## Impact

- Affected code: file komponen frontend baru/terkait dashboard utama dan styling utilitas Tailwind yang digunakan.
- API impact: Tidak ada perubahan API backend pada tahap ini.
- Dependencies: Tidak ada dependency runtime baru yang wajib.
- UX impact: Pengguna mendapatkan layout dashboard generator yang terstruktur untuk memulai proses generasi.
