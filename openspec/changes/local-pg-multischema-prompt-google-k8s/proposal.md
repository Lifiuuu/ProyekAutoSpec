## Why

AutoSpec masih menyisakan asumsi Supabase dan belum menetapkan arsitektur multi-schema PostgreSQL untuk isolasi hasil generate per pengguna. Selain itu, system prompt generator JSON belum cukup ketat dalam Bahasa Inggris, panel review SQL belum sepenuhnya mencerminkan artefak prompt (termasuk pemisahan function vs stored procedure), dan deployment artifacts (Docker/K8s) belum disejajarkan dengan kredensial cluster hackathon. Perubahan ini diperlukan agar produk siap dipakai pada local server PostgreSQL, mendukung schema-per-generation, dan bisa dideploy stabil ke Kubernetes.

## What Changes

- Menghapus ketergantungan Supabase dari requirement dan menggantinya dengan konfigurasi local PostgreSQL:
  - `DB_CONNECTION=pgsql`
  - `DB_HOST=127.0.0.1`
  - `DB_PORT=5432`
  - `DB_DATABASE=dbautospec`
  - `DB_USERNAME=postgres`
  - `DB_PASSWORD=`
- Menetapkan arsitektur multi-schema: satu main schema untuk data user + histori generate, dan schema terpisah yang dibuat setiap kali user menjalankan generate prompt.
- Memperketat system prompt menjadi Bahasa Inggris dengan format JSON yang wajib, daftar type yang dibatasi, aturan FK integer, serta aturan trigger PostgreSQL hanya memanggil function (bukan procedure).
- Memodifikasi panel review frontend tanpa mengubah desain visual agar menampilkan kategori sesuai keluaran prompt: `DDL`, `DML`, `DCL (Credentials)`, `Functions`, `Stored Procedures`, dan `Triggers`.
- Menetapkan requirement integrasi Google Login menggunakan client credential yang diberikan untuk environment deployment.
- Memperbarui Docker/Kubernetes requirement agar sesuai deployment ke cluster hackathon (`semute`) termasuk domain ingress dan kredensial database tim.

## Capabilities

### New Capabilities
- `local-postgres-multischema-generation`: Mendukung pembuatan schema baru per generate request dengan pelacakan histori per user di main schema.
- `strict-english-json-schema-prompt`: Menegakkan system prompt Bahasa Inggris yang ketat untuk keluaran JSON murni sesuai kontrak.
- `sql-review-panel-expanded-categories`: Menyediakan pemisahan review SQL untuk DDL, DML, DCL, Functions, Stored Procedures, dan Triggers.
- `google-login-deployment-credentials`: Menyediakan konfigurasi OAuth Google yang siap dipakai pada backend deployment.
- `hackathon-k8s-aligned-deployment`: Menyelaraskan manifest Docker/K8s dengan domain, namespace, dan credential hackathon.

### Modified Capabilities
- `main-dashboard-generator-layout`: Menambah tab review SQL kategori function/procedure/trigger serta label credentials pada DCL tanpa mengubah bahasa desain UI.
- `autospec-auth-api-integration`: Menegaskan penggunaan konfigurasi Google OAuth produksi yang ditentukan.

## Impact

- Affected code:
  - `app/Services/GenerationService.php` (prompt ketat + alur multi-schema)
  - Layer persistence user/history schema di backend
  - `resources/js/Components/Dashboard.jsx` dan view dashboard terkait panel review SQL
  - `.env.example`, `k8s/*.yaml`, `k8s/README.md`, dan berkas Docker deployment
- API impact:
  - Respons generate dapat memuat section SQL terpisah untuk `function`, `stored_procedure`, dan `trigger`
  - Metadata histori menyimpan nama schema target per generate
- Dependencies:
  - Tidak wajib dependency baru untuk core; integrasi Google memakai parameter env yang sudah didukung backend
- Security/ops:
  - Secret harus dipindah dari manifest contoh ke secret runtime cluster
  - Kredensial Google dan DB dikelola lewat secret, bukan hardcoded di source
