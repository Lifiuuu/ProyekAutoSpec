## Why

Pengguna perlu cara yang lebih cepat untuk menghasilkan query data tanpa menulis SQL manual. Dengan Supabase Edge Functions, alur NLP to SQL bisa dipusatkan di backend yang aman, memanfaatkan Authorization header yang sudah ada di `apiClient`, dan mengembalikan data JSON yang langsung bisa dipakai frontend.

## What Changes

- Menambahkan alur baru dari frontend ke Supabase Edge Function untuk menerjemahkan teks natural menjadi SQL dan mengeksekusinya.
- Mengubah kontrak respons menjadi JSON terstruktur, bukan raw SQL, agar frontend cukup merender data hasil.
- Memastikan request membawa header Authorization yang sudah tersedia di `apiClient` untuk menjaga akses berbasis RLS.
- Menyediakan scaffolding file fungsi Supabase, pengujian lokal, dan integrasi ke komponen React yang memanggil `apiClient`.

## Capabilities

### New Capabilities
- `nlp-to-sql-supabase-edge-function`: alur NLP ke SQL berbasis Supabase Edge Functions yang menerima prompt natural language, mengeksekusi query, dan mengembalikan JSON ke frontend.

### Modified Capabilities
- None.

## Impact

- Affected code: `supabase/functions/`, `apiClient.ts`, dan komponen React yang memicu proses generate.
- API impact: menambahkan endpoint Edge Function baru dengan request/response JSON terstruktur.
- Dependencies: Supabase Edge Functions, otentikasi Authorization header yang sudah ada, dan akses database dengan RLS.
- UX impact: pengguna bisa melakukan query data dengan bahasa natural tanpa melihat atau menyalin raw SQL.