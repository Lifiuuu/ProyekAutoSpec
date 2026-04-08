## Why

Struktur frontend AutoSpec saat ini masih monolitik dalam satu Blade component, sehingga sulit dipelihara dan diperluas. Refaktorisasi ke React component architecture yang modular diperlukan sekarang agar dashboard dapat mengikuti standar profesional web AI, mempermudah testing, dan mempersiapkan integrasi dinamis dengan Mock API Prism untuk riwayat history.

## What Changes

- Membuat folder Layout di `resources/js/Components` berisi Sidebar.jsx, Navbar.jsx, Header.jsx, dan MainContent.jsx.
- Membuat AppLayout.jsx sebagai container utama yang mengintegrasikan semua komponen Layout.
- Menerapkan layout grid responsif dengan Sidebar fixed di kiri dan MainContent yang scrollable.
- Menerapkan palet warna Semute (#1E1E1E, #141414, #234C6A, #456882) secara konsisten di semua komponen.
- Menyiapkan struktur komponen agar siap menerima data dinamis dari endpoint `/api/history` (Mock API Prism).
- Menambahkan fitur History dengan icon, status (Success/Pending/Error), dan timestamp untuk setiap item generasi.

## Capabilities

### New Capabilities
- `layout-component-architecture`: Struktur komponen React modular (Sidebar, Navbar, Header, MainContent, AppLayout) dengan peran yang jelas.
- `sidebar-history-navigation`: Komponen Sidebar dengan daftar riwayat generasi database, menampilkan icon, status, dan timestamp per item.
- `responsive-layout-styling`: Sistem layout responsif menggunakan CSS Grid/Flexbox dengan palet warna Semute yang konsisten dan transisi smooth.
- `api-integration-ready`: Struktur props dan state yang memungkinkan pemanggilan endpoint `/api/history` untuk data dinamis tanpa perubahan komponen utama.

### Modified Capabilities
- None.

## Impact

- Affected code: `resources/js/Components/Layout/`, `resources/js/app.js`, `resources/css/app.css`.
- New folder structure: `resources/js/Components/Layout/Sidebar.jsx`, `Navbar.jsx`, `Header.jsx`, `MainContent.jsx`, `AppLayout.jsx`.
- Affected styling: Palet warna Semute diterapkan ke semua komponen, animasi transisi untuk interaksi Sidebar.
- No API contract changes; struktur siap untuk integrasi Mock API Prism nantinya.
