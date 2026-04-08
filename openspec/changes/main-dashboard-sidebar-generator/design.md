## Context

Aplikasi membutuhkan halaman dashboard utama untuk alur generasi berbasis prompt natural language. Saat ini belum ada komponen yang menyatukan area histori, input prompt, pemilihan target database dialect, dan tombol aksi generate dalam satu layout konsisten. Kebutuhan desain menekankan struktur dua kolom agar sidebar dan area kerja utama mudah dipindai serta siap diperluas pada iterasi berikutnya.

## Goals / Non-Goals

**Goals:**
- Membangun komponen MainDashboard dengan layout Sidebar kiri dan konten utama kanan.
- Menampilkan logo AutoSpec dan daftar Histori Generasi di area Sidebar.
- Menyediakan NLP Prompt Box (textarea besar) dengan border #234C6A dan placeholder Bikinin database perpustakaan....
- Menyediakan Dialect Dropdown dengan PostgreSQL aktif serta MySQL, MariaDB, SQLite sebagai opsi disabled, termasuk penanda Coming Soon.
- Menyediakan tombol Generate berwarna #456882 yang memanggil handler onGenerate.

**Non-Goals:**
- Mengimplementasikan logika backend untuk eksekusi query/generasi.
- Menyediakan histori dinamis dari API pada tahap ini.
- Menambahkan dukungan dialect selain opsi yang disebutkan.

## Decisions

1. Gunakan komponen terpisah bernama MainDashboard.
- Rationale: Memisahkan concern dashboard utama dari halaman lain dan memudahkan pengujian komponen.
- Alternative considered: Menambah markup langsung pada halaman existing.
- Why not: Sulit dipelihara dan memperbesar coupling pada view utama.

2. Gunakan layout dua kolom responsif dengan Sidebar tetap jelas secara visual.
- Rationale: Memastikan area histori tetap tersedia saat pengguna menulis prompt.
- Alternative considered: Layout satu kolom bertahap.
- Why not: Mengurangi kejelasan hirarki informasi untuk use case generator.

3. Gunakan state controlled untuk prompt dan dialect, serta event handler eksplisit untuk generate.
- Rationale: Memudahkan validasi input dan integrasi fungsi lanjut.
- Alternative considered: Uncontrolled form.
- Why not: Kurang fleksibel untuk integrasi proses generate dan disable rule per option.

4. Tampilkan opsi non-aktif dengan label Coming Soon pada dropdown.
- Rationale: Mengkomunikasikan roadmap fitur tanpa membuka opsi yang belum didukung.
- Alternative considered: Menyembunyikan opsi non-aktif.
- Why not: Tidak memberi visibilitas fitur yang akan datang kepada pengguna.

## Risks / Trade-offs

- [Logo tidak konsisten rasio tampilannya] -> Mitigasi: Tetapkan area logo dengan ukuran/padding yang stabil di Sidebar.
- [Opsi disabled tetap bisa dipilih pada beberapa browser lama] -> Mitigasi: Tambahkan validasi sebelum onGenerate dijalankan.
- [Layout dua kolom terlalu padat pada layar kecil] -> Mitigasi: Terapkan breakpoint untuk fallback vertikal di mobile.
