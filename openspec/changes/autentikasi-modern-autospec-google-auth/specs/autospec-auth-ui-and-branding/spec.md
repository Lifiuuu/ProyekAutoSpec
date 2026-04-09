## ADDED Requirements

### Requirement: Login and registration pages SHALL follow AutoSpec visual branding
The system SHALL provide Login and Registrasi pages with consistent AutoSpec palette and styling tokens, including background `#1E1E1E`, card `#141414`, border `#234C6A`, accent `#456882`, and text `#F7F8F0`.

#### Scenario: Auth page visual consistency
- **WHEN** pengguna membuka halaman Login atau Registrasi
- **THEN** halaman menampilkan palet warna AutoSpec, rounded card, soft shadow, dan tipografi yang konsisten

### Requirement: Auth forms SHALL display AutoSpec logo and minimal SaaS layout
The system SHALL show AutoSpec logo at the top area of both auth forms and maintain a clean layout optimized for desktop and mobile viewports.

#### Scenario: Branding visibility on auth forms
- **WHEN** halaman Login atau Registrasi dirender
- **THEN** logo AutoSpec terlihat jelas di bagian atas card formulir dan tata letak tetap rapi di layar kecil maupun besar

### Requirement: Login and registration forms SHALL expose required credential fields
The system SHALL provide input fields for Email and Password on Login and Registrasi flows with clear labels and validation feedback states.

#### Scenario: Required inputs rendered
- **WHEN** pengguna membuka form Login atau Registrasi
- **THEN** sistem menampilkan field Email dan Password dengan state validasi yang dapat dipahami pengguna
