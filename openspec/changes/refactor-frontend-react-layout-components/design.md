## Context

Frontend AutoSpec saat ini menggunakan Blade template yang menggabungkan semua elemen (sidebar, navbar, header, main) dalam satu file komponen. Arsitektur ini membuat maintenance sulit, testing terbatas, dan sulit untuk ekspansi fitur dinamis. Tech stack mencakup React.js di frontend (melalui Vite), Laravel di backend, dan Prism untuk mocking API. Desain ini bertujuan memisahkan concerns menjadi komponen React yang terisolasi namun terintegrasi.

## Goals / Non-Goals

**Goals:**
- Membangun modular component architecture dengan separation of concerns yang jelas (Layout, Sidebar, Navbar, Header, MainContent).
- Menyediakan layout responsif dengan Sidebar fixed di desktop dan accessible di mobile.
- Menerapkan palet warna Semute (#1E1E1E background, #141414 sidebar, #234C6A border, #456882 accent) secara konsisten.
- Menyiapkan struktur props/state untuk menerima data dinamis dari `/api/history` endpoint Prism mock.
- Memastikan transisi smooth dan feedback visual saat user berinteraksi dengan Sidebar.

**Non-Goals:**
- Tidak mengubah backend API endpoint atau kontrak data.
- Tidak membangun fitur filtering/search history pada phase ini.
- Tidak mengganti Blade template sepenuhnya (hanya komponen layout).
- Tidak menambah dependency UI library eksternal di luar Vite/Tailwind yang sudah ada.

## Decisions

- **Lokasi komponen**: Ditempatkan di `resources/js/Components/Layout/` untuk memudahkan import dan organizational clarity.
  - Rationale: Lokasi terpisah dari main app logic membuat maintenance dan testing lebih mudah.
  - Alternatives considered: menempatkan di `src/` (ditolak karena project sudah menggunakan Laravel resources folder structure).

- **State management**: Komponen AppLayout sebagai parent mengelola state global layout, Sidebar dapat pass history data via props.
  - Rationale: Sederhana dan tidak memerlukan Redux/Context yang kompleks untuk skala ini.
  - Alternatives considered: Context API (deferred untuk iterasi berikutnya).

- **Styling approach**: Tailwind CSS classnames dengan custom color variables CSS pada root scope.
  - Rationale: Konsisten dengan pipeline Vite existingdan mudah dikustomisasi warna Semute.
  - Alternatives considered: styled-components (ditolak karena overhead).

- **Responsive behavior**: CSS Grid untuk desktop (sidebar fixed kiri, content scrollable kanan); media query untuk stack vertikal di mobile.
  - Rationale: Fleksibel dan performant tanpa JavaScript tambahan.
  - Alternatives considered: JavaScript-based resize listener (overhead, ditunda).

## Risks / Trade-offs

- [Risk: Breaking existing functionality saat refaktoring] → Mitigation: Tetap maintain Blade component template sebagai wrapper shim yang merender React component via entry point yang sama.
- [Risk: Performa rendering dengan komponen berjumlah banyak] → Mitigation: Hanya render history items yang visible (lazy load atau virtual scroll di iterasi berikutnya).
- [Risk: Palet warna Semute belum fully tested di semua state] → Mitigation: Dokumentasikan token warna di CSS file dan test secara manual di desktop/mobile.
- [Trade-off: History data masih mock data] → Mitigation: Struktur props dirancang agar mudah diganti dengan API call tanpa perubahan render logic.

## Migration Plan

- Langkah 1: Buat folder Layout dan component files tanpa perubahan existing Blade.
- Langkah 2: Implementasikan komponen satu per satu (Navbar → Header → Sidebar → MainContent → AppLayout).
- Langkah 3: Testkan visual dan responsiveness di browser.
- Langkah 4: Integrasi ke template Blade (jika perlu).
- Rollback plan: Hapus folder Layout dan revert CSS changes; Blade template tidak berubah jadi tidak perlu rollback.

## Open Questions

- Apakah Sidebar harus collapsible di desktop atau selalu tampil?
- Berapa maksimal item history yang ditampilkan sebelum pagination?
- Apakah profile user dan notifikasi di Navbar perlu ditampilkan pada MVP ini?
