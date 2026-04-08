## Context

Fitur ini menambahkan jalur NLP to SQL berbasis Supabase Edge Functions. Saat ini frontend sudah memiliki `apiClient` dan Authorization header, jadi desain terbaik adalah memindahkan pemrosesan prompt dan eksekusi SQL ke Edge Function agar akses database tetap berada di belakang RLS dan frontend hanya menerima JSON terstruktur.

## Goals / Non-Goals

**Goals:**
- Menyediakan endpoint Edge Function untuk menerima prompt natural language.
- Menggunakan Authorization header yang sudah ada di `apiClient` untuk autentikasi request.
- Mengembalikan hasil dalam bentuk JSON terstruktur, bukan raw SQL.
- Mendukung integrasi langsung ke komponen React yang memicu generate.
- Menyediakan langkah testing lokal untuk validasi end-to-end.

**Non-Goals:**
- Tidak mengubah strategi RLS di database.
- Tidak membangun model AI baru di frontend.
- Tidak menyimpan hasil generated SQL sebagai output utama ke UI.
- Tidak menambah sistem observability baru di luar kebutuhan fitur ini.

## Decisions

1. Gunakan Supabase Edge Function sebagai layer orkestrasi utama.
- Rationale: pemrosesan prompt, transformasi SQL, dan eksekusi database lebih aman saat berada di backend dekat database.
- Alternatives considered: menjalankan seluruh logika di frontend atau server terpisah.
- Why not: frontend akan mengekspos logika sensitif, dan server terpisah menambah kompleksitas deployment.

2. Kembalikan JSON hasil query ke frontend, bukan raw SQL.
- Rationale: frontend hanya perlu render data dan metadata hasil.
- Alternatives considered: mengembalikan SQL lalu dieksekusi lagi di frontend.
- Why not: raw SQL memperbesar risiko penyalahgunaan dan tidak cocok dengan alur UI yang ingin langsung menampilkan hasil.

3. Reuse Authorization header dari `apiClient` tanpa menambah mekanisme auth baru.
- Rationale: konsisten dengan infrastruktur yang sudah ada dan mendukung RLS.
- Alternatives considered: token custom atau public endpoint.
- Why not: menambah surface area keamanan dan memperumit pemeliharaan.

4. Simpan kontrak request/response dalam bentuk JSON eksplisit.
- Rationale: mudah ditest, mudah divalidasi, dan jelas untuk React serta Edge Function.
- Alternatives considered: form-data atau payload bebas.
- Why not: lebih sulit divalidasi dan kurang cocok untuk komunikasi terstruktur.

## Risks / Trade-offs

- [Response JSON terlalu besar untuk query kompleks] → Mitigasi: batasi field metadata, dukung pagination, dan pertimbangkan limit default.
- [Prompt menghasilkan SQL yang tidak aman] → Mitigasi: validasi query sebelum eksekusi dan pertahankan akses lewat Authorization/RLS.
- [Edge Function gagal lokal karena konfigurasi env] → Mitigasi: dokumentasikan `supabase start` / `supabase functions serve` sebagai langkah testing lokal.
- [Perbedaan format response antar environment] → Mitigasi: definisikan schema JSON yang konsisten dan gunakan error object terstruktur.

## Migration Plan

1. Buat folder fungsi di `supabase/functions/nlp-to-sql/`.
2. Implementasikan handler Edge Function dengan parsing JSON request dan Authorization header.
3. Tambahkan validasi input dan eksekusi query yang mengembalikan JSON.
4. Integrasikan `apiClient` di React untuk memanggil endpoint baru.
5. Jalankan testing lokal terhadap fungsi dan komponen React.
6. Jika perlu rollback, nonaktifkan konsumsi endpoint di frontend sambil mempertahankan fungsi di Supabase.

## Open Questions

- Apakah response JSON perlu menyertakan metadata pagination dari awal?
- Apakah hasil query akan disimpan sebagai artefak terpisah untuk audit atau cukup dikembalikan ke frontend?