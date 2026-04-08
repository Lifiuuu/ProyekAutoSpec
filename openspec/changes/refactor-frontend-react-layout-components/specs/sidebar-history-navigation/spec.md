## ADDED Requirements

### Requirement: Sidebar display generation history
Sistem SHALL menampilkan daftar riwayat generasi database pada Sidebar dengan informasi yang jelas untuk setiap item.

#### Scenario: History items display with icon, status, and timestamp
- **WHEN** halaman Sidebar dirender dengan data history
- **THEN** setiap item menampilkan: icon (tabel/file), nama generasi, status (Success/Pending/Error), dan timestamp dalam format yang readable

#### Scenario: History item click triggers action
- **WHEN** user mengklik salah satu item history di Sidebar
- **THEN** callback `onItemClick` dipanggil dengan item data, parent dapat merespons (misal: load preview atau detail)

### Requirement: History list prepared for API data
Sidebar SHALL didesain agar dapat menerima data dari `/api/history` endpoint tanpa perubahan rendering logic.

#### Scenario: Sidebar renders mock history data
- **WHEN** Sidebar.jsx dirender dengan `historyItems` array dari mock data
- **THEN** list items tampil dengan benar, siap untuk diganti dengan API response nanti
