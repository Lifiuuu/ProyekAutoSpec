## Context

Perubahan ini menggabungkan kebutuhan arsitektural backend (migrasi dari asumsi Supabase ke local PostgreSQL + multi-schema per generate), kontrak AI output (system prompt JSON ketat berbahasa Inggris), UX review hasil SQL (kategori lebih lengkap tanpa ubah desain visual), autentikasi Google berbasis kredensial yang disediakan user, serta kesiapan deploy Docker/Kubernetes di cluster hackathon.

Kendala utama:
- Harus menjaga konsistensi UI existing; hanya struktur konten panel review yang berubah.
- Backend harus aman membuat schema dinamis berulang kali tanpa mencampur data user antar schema.
- Prompt LLM harus deterministic agar parser backend stabil dan type mapping tetap sesuai aturan.
- Deployment cluster membutuhkan manifest yang sinkron dengan domain/namespace/DB tim dan secret hygiene yang benar.

## Goals / Non-Goals

**Goals:**
- Menghilangkan requirement Supabase dari alur runtime dan dokumentasi requirement.
- Menetapkan default local PostgreSQL env seperti yang diminta.
- Menerapkan model `main schema + generated schema per prompt` untuk multi-schema isolation.
- Mengganti system prompt ke Bahasa Inggris, lebih strict, dan selaras dengan validator backend.
- Menampilkan kategori review SQL: DDL, DML, DCL (credentials), Functions, Stored Procedures, Triggers.
- Mengaktifkan Google login menggunakan client id/secret yang diberikan via konfigurasi secret env.
- Menyesuaikan Docker/K8s agar siap deploy ke namespace `semute` dan domain hackathon.

**Non-Goals:**
- Tidak mendesain ulang tampilan visual frontend (warna, layout besar, typography).
- Tidak membangun sistem RBAC kompleks lintas schema pada fase ini.
- Tidak memaksa migrasi historis data lama antar environment di perubahan requirement ini.

## Decisions

1. Gunakan PostgreSQL local-first env sebagai baseline requirement
- Nilai env default ditetapkan eksplisit: `127.0.0.1:5432`, db `dbautospec`, user `postgres`.
- Rationale: menyamakan ekspektasi dev runtime dan menghapus coupling Supabase.

2. Terapkan main schema untuk identitas + histori, schema dinamis per generate
- Main schema menyimpan user, prompt, metadata hasil generate, dan nama schema target.
- Setiap generate membuat schema unik (contoh: `gen_<userid>_<timestamp>`), lalu DDL dieksekusi pada search_path schema tersebut.
- Rationale: isolasi data per hasil generate dan traceability histori.

3. Terapkan strict English prompt dengan JSON contract eksplisit
- Prompt mewajibkan JSON murni dengan root keys: `tables`, `stored_procedures`, `functions`, `triggers`.
- Type terbatas: `id`, `string`, `integer`, `text`, `boolean`, `date`, `datetime`, `decimal`.
- `id` hanya untuk PK kolom `id`; foreign key wajib `integer`.
- Trigger PostgreSQL wajib memanggil function, bukan procedure.
- Rationale: menurunkan error parsing/DDL translation.

4. Perluas kategori SQL review panel tanpa mengubah gaya visual
- Existing card/tabs/layout dipertahankan.
- Kategori logic ditambah agar `function`, `stored_procedure`, dan `trigger` tampil terpisah; DCL diberi konteks credentials.
- Rationale: user melihat output sesuai struktur prompt dan mudah review keamanan.

5. Konfigurasi Google OAuth melalui secrets, bukan hardcode source
- Nilai client id/secret dipasang pada environment deployment.
- Source code hanya membaca env.
- Rationale: keamanan dan portabilitas antar environment.

6. Selaraskan deployment ke cluster hackathon
- Gunakan namespace `semute`, host ingress `semute.hackathon.sev-2.com`, dan DB credentials tim.
- Manifest example tidak boleh menyimpan secret nyata.
- Rationale: deployment reproducible dan aman.

## Risks / Trade-offs

- [Schema tumbuh cepat karena per-generate] -> Mitigasi: simpan metadata timestamp + owner dan siapkan kebijakan cleanup/retention.
- [Prompt terlalu ketat menyebabkan output minim] -> Mitigasi: tetap izinkan arrays kosong untuk function/procedure/trigger saat tidak dibutuhkan.
- [Google OAuth credential bocor di repo] -> Mitigasi: hanya simpan di secret runtime, rotasi credential jika sudah terlanjur terekspos.
- [Perbedaan DB host lokal vs cluster] -> Mitigasi: dokumentasi env profile terpisah (`local`, `cluster`) dan kustomisasi k8s configmap.

## Migration Plan

1. Perbarui requirement OpenSpec untuk menghapus Supabase assumptions dan menetapkan env PostgreSQL lokal.
2. Perbarui requirement backend generation agar membuat schema dinamis per generate dan menulis histori ke main schema.
3. Perbarui requirement prompt LLM ke English strict JSON contract.
4. Perbarui requirement dashboard review panel agar kategori SQL mengikuti output prompt (DDL/DML/DCL/Functions/Stored Procedures/Triggers).
5. Perbarui requirement auth integration untuk Google OAuth credentials via env.
6. Perbarui requirement deployment Docker/K8s sesuai namespace/domain/credential hackathon dan praktik secret aman.

Rollback:
- Gunakan kembali spec requirement sebelumnya dengan mengarsipkan change ini jika implementasi belum dimulai.
- Jika implementasi parsial sudah berjalan, rollback dilakukan per capability (prompt, panel review, deployment manifest) tanpa mengubah desain UI.

## System Prompt (English, Strict)

```text
You are a highly strict Database Schema Generator.
Convert the user's database description into PURE JSON only.

MANDATORY RULES:
1) Output MUST be valid JSON. No markdown, no explanation, no code fences.
2) The root JSON structure MUST be exactly:
{
  "tables": [
    {
      "name": "table_name",
      "columns": [
        {"type": "id", "name": "id"},
        {"type": "integer", "name": "item_id"},
        {"type": "string", "name": "name"}
      ],
      "dummy_data": [
        {"name": "Book"}
      ]
    }
  ],
  "stored_procedures": [
    {"name": "procedure_name", "definition": "CREATE OR REPLACE PROCEDURE ..."}
  ],
  "functions": [
    {"name": "function_name", "definition": "CREATE OR REPLACE FUNCTION ..."}
  ],
  "triggers": [
    {"name": "trigger_name", "definition": "CREATE TRIGGER ..."}
  ]
}
3) Allowed column types are ONLY:
   "id", "string", "integer", "text", "boolean", "date", "datetime", "decimal".
   Never use raw SQL types such as VARCHAR, INT, BIGINT, TIMESTAMP.
4) Use "id" ONLY for the primary key column named exactly "id".
   For foreign keys (e.g., user_id, product_id), you MUST use "integer".
5) PostgreSQL trigger rule is strict:
   a trigger can call a FUNCTION only, never a PROCEDURE.
6) Keep naming lowercase_with_underscores for table and column names.
7) If an item is not needed, return an empty array for that key.
8) Return only one JSON object and nothing else.
```
