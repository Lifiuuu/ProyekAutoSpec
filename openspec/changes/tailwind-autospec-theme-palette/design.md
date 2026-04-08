## Context

Aplikasi Laravel + Vite membutuhkan sistem warna frontend yang konsisten. Saat ini warna belum ditetapkan sebagai token tema sehingga implementasi rentan memakai nilai hex langsung dan tidak seragam antar halaman. Proyek menargetkan pemakaian class utilitas Tailwind untuk menjaga konsistensi gaya, dengan kebutuhan warna resmi AutoSpec: background `#1E1E1E`, primary `#1B3C53`, secondary `#234C6A`, accent `#456882`, dan text `#F7F8F0`.

## Goals / Non-Goals

**Goals:**
- Menetapkan token warna AutoSpec sebagai variabel CSS global.
- Memetakan token tersebut ke tema warna Tailwind supaya class utilitas seperti `bg-primary` dan `text-accent` tersedia.
- Menjaga implementasi agar mudah dipelihara dan aman untuk perluasan tema di masa depan.

**Non-Goals:**
- Mendesain ulang seluruh UI atau komponen.
- Menambah mode tema lain (mis. light/dark toggle dinamis).
- Mengubah endpoint backend atau kontrak API.

## Decisions

1. Simpan warna sebagai CSS variable pada layer global stylesheet.
- Rationale: Memusatkan source of truth warna dan memudahkan perubahan nilai tanpa mengubah banyak komponen.
- Alternative considered: Menulis hex langsung di `tailwind.config.js`.
- Why not: Kurang fleksibel untuk kebutuhan runtime/theming dan mendorong duplikasi.

2. Definisikan warna Tailwind sebagai `rgb(var(--token) / <alpha-value>)` atau nilai berbasis variabel yang kompatibel utilitas opacity.
- Rationale: Memungkinkan pemakaian class opacity Tailwind (`bg-primary/80`) tetap berfungsi.
- Alternative considered: Mendefinisikan sebagai string hex statis.
- Why not: Tidak mendukung fleksibilitas opacity berbasis utilitas sebaik pendekatan variabel.

3. Gunakan namespace warna sederhana: `background`, `primary`, `secondary`, `accent`, `text`.
- Rationale: Naming langsung sesuai bahasa desain AutoSpec dan mudah dipakai tim.
- Alternative considered: Prefix panjang seperti `autospec-primary`.
- Why not: Menambah verbosity tanpa manfaat signifikan pada konteks proyek saat ini.

## Risks / Trade-offs

- [Konflik konfigurasi Tailwind yang sudah ada] -> Mitigasi: Merge perubahan ke blok `theme.extend.colors` tanpa menghapus warna lain.
- [Variabel CSS tidak termuat pada entry stylesheet] -> Mitigasi: Tempatkan variabel pada file CSS global yang sudah diimpor oleh Vite.
- [Ketidaksesuaian format warna variabel terhadap parser Tailwind] -> Mitigasi: Pakai format variabel yang umum dipakai Tailwind dan verifikasi build frontend.
