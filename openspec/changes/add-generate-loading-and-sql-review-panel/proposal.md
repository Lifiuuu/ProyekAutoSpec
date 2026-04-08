## Why

Saat ini aksi Generate belum memberikan umpan balik proses sehingga pengguna tidak tahu kapan AI sedang bekerja atau sudah selesai. Selain itu, hasil SQL belum ditampilkan dalam panel review terstruktur, sehingga user tidak bisa meninjau skrip sebelum eksekusi final ke Kubernetes.

## What Changes

- Menambahkan state `isLoading` saat tombol Generate diklik.
- Menambahkan animasi loading modern selama proses AI berjalan.
- Menampilkan SQL Review Panel setelah proses AI selesai.
- SQL Review Panel menggunakan komponen editor kode untuk menampilkan hasil SQL.
- Panel review menampilkan kategori skrip: DDL, DML, DCL, dan Trigger agar dapat ditinjau sebelum eksekusi final ke Kubernetes.

## Capabilities

### New Capabilities
- `generate-loading-and-sql-review`: Menyediakan alur loading state dan panel review SQL berbasis code editor setelah generate selesai.

### Modified Capabilities
- `main-dashboard-generator-layout`: Menambahkan behavior loading dan post-generate review panel pada antarmuka MainDashboard.

## Impact

- Affected code: komponen dashboard generator, handler generate, dan komponen panel editor SQL.
- API impact: Tidak ada perubahan kontrak API wajib pada tahap proposal; hasil AI diasumsikan sudah tersedia dari alur existing/mock.
- Dependencies: Mungkin membutuhkan komponen editor kode frontend (native textarea enhanced atau library editor ringan jika belum ada).
- UX impact: Pengguna mendapat feedback proses yang jelas dan tahap validasi SQL sebelum eksekusi ke Kubernetes.
