## 1. Setup & Folder Structure

- [x] 1.1 Buat folder `resources/js/Components/Layout/` untuk menyimpan semua komponen layout.
- [x] 1.2 Buat file Navbar.jsx dengan struktur component skeleton dan export default.
- [x] 1.3 Buat file Header.jsx dengan struktur component skeleton dan export default.
- [x] 1.4 Buat file Sidebar.jsx dengan struktur component skeleton dan export default.
- [x] 1.5 Buat file MainContent.jsx dengan struktur component skeleton dan export default.
- [x] 1.6 Buat file AppLayout.jsx sebagai root wrapper yang integrasikan keempat komponen.

## 2. Navbar Component Implementation

- [x] 2.1 Implementasikan Navbar.jsx dengan logo AutoSpec, workspace indicator, dan buttons (New Session, Recent History).
- [x] 2.2 Tambahkan props interface: bisa menerima status dan callbacks dari parent.
- [x] 2.3 Terapkan styling Semute (#456882 accent untuk tombol, bg #1E1E1E).
- [x] 2.4 Verifikasi Navbar render di desktop dan mobile tanpa layout break.

## 3. Header Component Implementation

- [x] 3.1 Implementasikan Header.jsx dengan judul halaman dinamis dan status badge.
- [x] 3.2 Tambahkan props `title` dan `subtitle` dari parent AppLayout.
- [x] 3.3 Terapkan styling Semute (border #234C6A, bg #1E1E1E).
- [x] 3.4 Pastikan header responsif pada viewport kecil.

## 4. Sidebar Component Implementation

- [x] 4.1 Implementasikan Sidebar.jsx untuk menampilkan daftar riwayat history.
- [x] 4.2 Tambahkan mock data structure dengan field: id, name, status, timestamp, icon_type, description.
- [x] 4.3 Render history items dengan icon, status badge (Success/Pending/Error), dan timestamp.
- [x] 4.4 Implementasikan hover effect dengan transisi smooth 200ms pada background color.
- [x] 4.5 Tambahkan callback props `onHistoryItemClick` untuk handle click event.
- [x] 4.6 Verifikasi scrolling behavior ketika history items lebih dari 5 item.

## 5. MainContent Component Implementation

- [x] 5.1 Implementasikan MainContent.jsx sebagai container untuk konten dashboard utama.
- [x] 5.2 Tambahkan props children atau region props untuk receive dashboard sections.
- [x] 5.3 Terapkan scrollable area jika konten lebih panjang dari viewport.
- [x] 5.4 Pastikan MainContent tetap accessible di mobile.

## 6. AppLayout Integration

- [x] 6.1 Implementasikan AppLayout.jsx yang import dan render Navbar, Header, Sidebar, MainContent.
- [x] 6.2 Setup CSS Grid layout: Sidebar fixed kiri span 300px, MainContent flex grow.
- [x] 6.3 Tambahkan mock history data structure dan pass ke Sidebar via props.
- [x] 6.4 Implementasikan onHistoryItemClick handler opsional di AppLayout.
- [x] 6.5 Pastikan layout display correctly di localhost/main-dashboard.

## 7. Styling & Color Palette

- [x] 7.1 Update `resources/css/app.css` dengan CSS custom properties untuk palet Semute (#1E1E1E, #141414, #234C6A, #456882).
- [x] 7.2 Implementasikan CSS Grid media query untuk responsive: desktop (sidebar tetap fixed) vs mobile (sidebar stack atau collapse hint).
- [x] 7.3 Terapkan transisi smooth 200ms untuk hover state di Sidebar items.
- [x] 7.4 Verifikasi semua border, background, text color menggunakan custom properties, tidak hardcoded.

## 8. Testing & Validation

- [x] 8.1 Jalankan `npm run build` dan verifikasi tidak ada error atau warning.
- [x] 8.2 Buka browser ke `/main-dashboard`, verifikasi AppLayout render dengan semua empat region (Navbar, Header, Sidebar, MainContent).
- [x] 8.3 Test desktop layout (≥1024px): Sidebar tetap di kiri, MainContent scrollable tanpa layout shift.
- [x] 8.4 Test mobile layout (<1024px): Layout responsive, tidak ada overlap, semua konten accessible.
- [x] 8.5 Test Sidebar history items: click item, hover effect smooth, status badge display correct.
- [x] 8.6 Verifikasi warna Semute konsisten di semua komponen (background, border, text, accent pada button).
- [x] 8.7 Dokumentasikan struktur props di setiap komponen (bisa di komentar JSDoc).
