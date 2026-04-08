## 1. API Error Detection

- [x] 1.1 Identifikasi jalur pemanggilan API generate SQL di komponen dashboard.
- [x] 1.2 Tambahkan matcher error untuk mendeteksi kegagalan struktur SQL dari response backend.
- [x] 1.3 Tambahkan fallback error handling jika payload backend tidak konsisten.

## 2. Rollback Toast Handling

- [x] 2.1 Implementasikan komponen/toast state untuk warning non-blocking.
- [x] 2.2 Tampilkan pesan exact saat rollback terdeteksi: Database telah dikembalikan ke kondisi semula secara aman (Rollback triggered).
- [x] 2.3 Pastikan toast muncul di posisi yang terlihat jelas dan hilang sesuai durasi yang ditetapkan.

## 3. Safe UI State Reset

- [x] 3.1 Pada jalur rollback error, reset state `isLoading` di blok finalisasi.
- [x] 3.2 Pastikan panel hasil sukses (SQL Review/overview) tidak menampilkan data stale setelah rollback.
- [x] 3.3 Pastikan tombol Generate kembali aktif setelah error rollback ditangani.

## 4. Integration and Verification

- [x] 4.1 Integrasikan alur error handling rollback ke flow generate tanpa mengubah behavior success path.
- [x] 4.2 Verifikasi manual skenario backend error struktur SQL menghasilkan toast rollback yang benar.
- [x] 4.3 Verifikasi manual UI tetap interaktif setelah toast muncul.
- [x] 4.4 Jalankan build frontend untuk memastikan perubahan tidak menimbulkan error kompilasi.
