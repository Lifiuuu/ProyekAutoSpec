## ADDED Requirements

### Requirement: Responsive Layout with Semute Color Palette
Sistem SHALL menyediakan layout responsif menggunakan CSS Grid/Flexbox dengan palet warna Semute (#1E1E1E, #141414, #234C6A, #456882) diterapkan konsisten di semua komponen.

#### Scenario: Desktop layout with fixed sidebar
- **WHEN** halaman dibuka di viewport desktop (≥1024px)
- **THEN** Sidebar tampil fixed di kiri, MainContent scrollable di kanan, tanpa overlap

#### Scenario: Mobile layout stacks vertically
- **WHEN** halaman dibuka di viewport mobile (<1024px)
- **THEN** Sidebar dan MainContent stack vertikal atau Sidebar collapsible, tetap accessible tanpa menutupi konten utama

### Requirement: Consistent Color Palette Application
Semua komponen SHALL menggunakan CSS custom properties untuk warna Semute agar konsisten dan mudah dikustomisasi.

#### Scenario: Color tokens applied to all components
- **WHEN** developer membuka devtools dan inspect elemen (border, background, text)
- **THEN** semua warna menggunakan tokens dari CSS root scope (--bg-primary: #1E1E1E, --sidebar: #141414, dll)

### Requirement: Smooth Transitions on Interaction
Sistem SHALL menerapkan transisi CSS smooth saat user berinteraksi dengan komponen (misal: hover state, Sidebar toggle).

#### Scenario: Smooth color transition on hover
- **WHEN** user hover di Sidebar history item
- **THEN** background color berubah smooth dalam 200ms tanpa jerky motion
