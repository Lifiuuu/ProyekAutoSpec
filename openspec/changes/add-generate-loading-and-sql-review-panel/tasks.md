## 1. Loading State Integration

- [x] 1.1 Tambahkan state `isLoading` pada alur klik tombol Generate di MainDashboard.
- [x] 1.2 Pastikan tombol Generate tidak bisa dipicu berulang selama `isLoading` bernilai true.
- [x] 1.3 Implementasikan pola async yang mengatur `isLoading` true saat mulai dan reset di blok finalisasi (success/failure).

## 2. Modern Loading Experience

- [x] 2.1 Tambahkan komponen/markup animasi loading modern yang tampil saat `isLoading` aktif.
- [x] 2.2 Terapkan styling animasi (spinner/skeleton/pulse) yang konsisten dengan tema AutoSpec.
- [x] 2.3 Pastikan loading indicator otomatis hilang saat proses AI selesai atau gagal.

## 3. SQL Review Panel

- [x] 3.1 Tambahkan state hasil SQL terstruktur untuk kategori DDL, DML, DCL, dan Trigger.
- [x] 3.2 Render SQL Review Panel hanya setelah hasil AI tersedia.
- [x] 3.3 Buat komponen editor kode read-only untuk menampilkan SQL dengan font monospace.
- [x] 3.4 Tampilkan section/tab kategori DDL, DML, DCL, dan Trigger dengan fallback teks jika kategori kosong.

## 4. MainDashboard Flow Update

- [x] 4.1 Integrasikan SQL Review Panel ke layout MainDashboard tanpa merusak struktur Sidebar dan konten utama.
- [x] 4.2 Perbarui perilaku tombol Generate agar menampilkan status loading dan status selesai proses.
- [x] 4.3 Pertahankan responsivitas layout pada desktop dan mobile setelah panel review ditambahkan.

## 5. Verification and Readiness

- [x] 5.1 Verifikasi manual bahwa alur berjalan berurutan: klik Generate → loading muncul → hasil SQL tampil.
- [x] 5.2 Verifikasi opsi SQL per kategori muncul sesuai format review sebelum eksekusi final ke Kubernetes.
- [x] 5.3 Jalankan build frontend untuk memastikan perubahan UI tidak memunculkan error kompilasi.
