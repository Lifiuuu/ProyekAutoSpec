## Why

Saat backend mengembalikan error pada struktur SQL, pengguna membutuhkan konfirmasi jelas bahwa sistem telah melakukan rollback secara aman. Tanpa feedback ini, pengguna bisa ragu terhadap konsistensi data dan status proses.

## What Changes

- Menambahkan error handling terstruktur pada fungsi pemanggilan API untuk proses SQL.
- Menangkap response error yang berkaitan dengan struktur SQL dari backend.
- Menampilkan peringatan Toast kepada pengguna saat rollback terpicu.
- Pesan Toast wajib berbunyi: Database telah dikembalikan ke kondisi semula secara aman (Rollback triggered).
- Menjaga alur UI agar kembali ke state aman setelah error.

## Capabilities

### New Capabilities
- `sql-rollback-error-toast`: Menyediakan notifikasi rollback berbasis toast ketika backend mengembalikan error struktur SQL.

### Modified Capabilities
- `generate-loading-and-sql-review`: Menambahkan jalur error handling API dan perilaku UI rollback pada proses generate.

## Impact

- Affected code: fungsi API call generate SQL, handler error, dan komponen toast/notifikasi di dashboard.
- API impact: Tidak mengubah kontrak endpoint; hanya menambahkan penanganan terhadap payload error.
- Dependencies: Dapat menggunakan komponen toast existing; jika belum ada, perlu implementasi toast ringan.
- UX impact: Pengguna mendapat kepastian keamanan data saat rollback terjadi.
