# Integrasi React Frontend dengan API Backend (Hapus Mock Data)

## What
Ganti logika peng-generate-an database yang saat ini memakai data dummy (mock) pada `Dashboard.jsx` menjadi pemanggilan API nyata ke endpoint backend (`/api/generate`).

## Why
Penggunaan mock data menghalangi integrasi end-to-end, pengujian, dan penggunaan backend AI yang sudah disediakan. Mengganti mock dengan HTTP request akan memungkinkan frontend menampilkan hasil nyata (runId, sql_ddl, schema_overview, dll) dan menampilkan error yang sebenarnya ketika terjadi kegagalan.

Location: `resources/js/Components/Dashboard.jsx`
