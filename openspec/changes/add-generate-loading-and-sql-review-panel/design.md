## Context

MainDashboard saat ini sudah memiliki alur input prompt, pemilihan dialect, dan tombol Generate. Namun interaksi generate belum menampilkan state proses dan belum menyediakan panel review SQL hasil AI sebelum eksekusi final ke Kubernetes. Fitur baru ini menambahkan pengalaman bertahap: loading feedback saat proses berlangsung dan SQL Review Panel saat hasil siap.

## Goals / Non-Goals

**Goals:**
- Menambahkan state `isLoading` saat generate berjalan.
- Menampilkan animasi loading modern agar pengguna mendapat feedback progres.
- Menampilkan SQL Review Panel setelah proses selesai.
- Menampilkan output SQL kategori DDL, DML, DCL, dan Trigger dalam komponen editor kode.
- Menyediakan tahap review sebelum eksekusi final ke Kubernetes.

**Non-Goals:**
- Menjalankan eksekusi SQL ke Kubernetes pada perubahan ini.
- Mendesain ulang penuh tata letak MainDashboard.
- Menambahkan parser SQL kompleks lintas dialect.

## Decisions

1. Gunakan state UI eksplisit: `isLoading`, `generatedSql`, dan `showReviewPanel`.
- Rationale: Memastikan transisi status dapat diprediksi dan mudah dites.
- Alternative considered: Hanya toggle class loading tanpa state terpisah.
- Why not: Sulit mengontrol kondisi rendering panel hasil.

2. Gunakan loading animation berbasis CSS utility (spinner + pulse skeleton ringan).
- Rationale: Modern, ringan, dan tidak menambah dependency berat.
- Alternative considered: Lottie/third-party animation package.
- Why not: Menambah bundle size dan kompleksitas integrasi.

3. Gunakan code editor component sederhana berbasis area monospaced read-only pada tahap awal.
- Rationale: Stabil, cepat diimplementasikan, cukup untuk kebutuhan review.
- Alternative considered: Editor library penuh seperti Monaco.
- Why not: Overhead tinggi untuk fase awal; bisa di-upgrade nanti.

4. Render SQL dalam tab/section kategori DDL, DML, DCL, Trigger.
- Rationale: Membantu user meninjau tiap tipe skrip secara terstruktur sebelum eksekusi.
- Alternative considered: Menampilkan satu blok SQL panjang.
- Why not: Sulit ditinjau dan meningkatkan risiko salah baca.

## Risks / Trade-offs

- [Loading tidak sinkron dengan proses async] -> Mitigasi: Bungkus proses generate dalam try/finally untuk reset state.
- [Panel review kosong saat output parsial] -> Mitigasi: Sediakan fallback teks per kategori bila data belum tersedia.
- [Editor sederhana kurang fitur] -> Mitigasi: Definisikan antarmuka komponen agar mudah diganti editor lebih canggih di iterasi berikutnya.
