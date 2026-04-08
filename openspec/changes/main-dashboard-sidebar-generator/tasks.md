## 1. Component Structure

- [x] 1.1 Buat komponen MainDashboard baru dan siapkan layout dua panel (Sidebar kiri, konten kanan).
- [x] 1.2 Tambahkan struktur Sidebar dengan area logo AutoSpec di bagian atas.
- [x] 1.3 Tambahkan section Histori Generasi di bawah area logo pada Sidebar.

## 2. Input and Dialect Controls

- [x] 2.1 Tambahkan NLP Prompt Box berupa textarea besar dengan placeholder Bikinin database perpustakaan....
- [x] 2.2 Terapkan border warna #234C6A pada textarea prompt.
- [x] 2.3 Tambahkan dropdown dialect database dengan PostgreSQL aktif sebagai opsi default.
- [x] 2.4 Tambahkan opsi MySQL, MariaDB, dan SQLite dalam status disabled dengan label Coming Soon.

## 3. Generate Action and Validation

- [x] 3.1 Tambahkan tombol Generate dengan warna #456882 pada area konten utama.
- [x] 3.2 Hubungkan klik tombol Generate ke fungsi onGenerate.
- [x] 3.3 Tambahkan validasi agar hanya dialect aktif yang diproses saat onGenerate dipanggil.

## 4. UX and Responsiveness

- [x] 4.1 Terapkan breakpoint agar layout dua panel tetap terbaca pada desktop dan beradaptasi pada mobile.
- [x] 4.2 Pastikan urutan visual tetap konsisten: logo, Histori Generasi, input prompt, dropdown, tombol Generate.
- [x] 4.3 Verifikasi state disabled pada opsi Coming Soon tetap terlihat jelas dan tidak bisa dipilih.
