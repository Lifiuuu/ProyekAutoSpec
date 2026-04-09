## Context

AutoSpec saat ini langsung masuk ke area dashboard, sehingga storytelling produk sebelum masuk fitur teknis masih kurang kuat untuk demo dan validasi bisnis. Landing page baru akan menjadi entry point utama yang mengarahkan user ke auth flow (login/register) sekaligus menampilkan nilai produk secara cepat lewat hero, fitur, dan deliverables.

Constraint utama:
- Harus mempertahankan identitas visual AutoSpec (palet gelap Semute).
- Perlu tetap responsif dan ringan walaupun menambahkan animasi.
- Harus sinkron dengan alur autentikasi baru yang sudah dibangun.
- CTA harus jelas dan tidak mengganggu akses langsung ke login/register.

## Goals / Non-Goals

**Goals:**
- Menyediakan landing page conversion-focused sebagai gerbang utama sebelum aplikasi.
- Menonjolkan value proposition AutoSpec melalui hero, feature grid, dan seksi `3 File Sakti`.
- Menyediakan CTA yang jelas ke halaman Register dan Login.
- Menyediakan visual polish (font modern, whitespace, motion reveal) yang terasa profesional.

**Non-Goals:**
- Tidak mengubah engine generate SQL/OpenAPI/Postman backend.
- Tidak menambahkan endpoint API baru.
- Tidak merombak seluruh dashboard selain memastikan route flow tetap konsisten.

## Decisions

1. Gunakan komponen landing tunggal berbasis section modular
- Rationale: mudah dirawat, jelas per-section, dan cepat dioptimasi.
- Alternatives: banyak halaman marketing. Ditolak karena scope demo butuh fokus.

2. Jadikan route `/` sebagai entry landing, dengan CTA ke auth flow
- Rationale: user pertama kali mendapat konteks nilai produk sebelum login.
- Alternatives: tetap langsung dashboard. Ditolak karena narasi demo kurang kuat.

3. Gunakan Framer Motion untuk fade/slide reveal ringan
- Rationale: meningkatkan persepsi modern tanpa animasi berlebihan.
- Alternatives: CSS-only animation. Ditolak karena orkestrasi scroll reveal lebih terbatas.

4. Gunakan token warna AutoSpec hard-consistent lintas section
- Rationale: menjaga konsistensi brand dari landing ke auth/dashboard.
- Alternatives: warna gradien berbeda per section. Ditolak karena berisiko fragmentasi visual.

5. Tampilkan `3 File Sakti` sebagai kartu deliverables eksplisit
- Rationale: memperjelas output nyata produk yang langsung dipahami juri/pengguna.
- Alternatives: menaruh deliverables di feature list biasa. Ditolak karena dampak visual lebih lemah.

## Risks / Trade-offs

- [Animasi berlebihan menurunkan performa] -> Mitigation: gunakan motion sederhana, trigger once, dan batasi blur/transform berat.
- [CTA ambigu antara login/register] -> Mitigation: urutan CTA primer-sekunder dan copywriting tegas.
- [Inkonsistensi flow route dengan auth guard] -> Mitigation: definisikan route matrix jelas (`/` landing, auth view, dashboard protected).
- [Landing terlalu padat informasi] -> Mitigation: pertahankan whitespace luas dan hierarki tipografi ketat.

## Migration Plan

1. Tambahkan dependency Framer Motion jika belum ada.
2. Implementasikan komponen landing sections (Hero, Features, Deliverables, Footer).
3. Hubungkan navbar/CTA ke Login dan Register.
4. Atur route root agar merender landing, tetap menjaga akses dashboard sesuai auth state.
5. Uji responsivitas mobile-desktop dan timing animasi.

Rollback:
- Kembalikan route root ke dashboard sebelumnya.
- Nonaktifkan render landing component tanpa memengaruhi auth/dashboard core.

## Open Questions

- Apakah tombol `Mulai Sekarang` harus menuju mode register default atau halaman auth terpisah `/register`?
- Apakah perlu menambahkan social proof (logo partner/testimonial) untuk demo final?
- Apakah animasi harus disabled otomatis untuk preferensi reduced motion?