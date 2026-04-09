## ADDED Requirements

### Requirement: Auth forms SHALL submit requests through apiClient with JSON accept contract
The system SHALL submit login and registration requests via `apiClient.js` using POST methods and include header `Accept: application/json`.

#### Scenario: Login request contract
- **WHEN** pengguna mengirim form Login
- **THEN** sistem mengirim request POST ke endpoint autentikasi dengan header `Accept: application/json`

### Requirement: Google sign-in entry point SHALL be available on login page
The system SHALL provide a prominent `Sign in with Google` action on Login page, including recognizable Google iconography.

#### Scenario: Google login action appears
- **WHEN** pengguna membuka halaman Login
- **THEN** tombol `Sign in with Google` terlihat jelas dan dapat dipicu untuk memulai alur autentikasi Google

### Requirement: Authentication failures SHALL show user-friendly toast feedback
The system SHALL display non-blocking toast notifications for invalid credentials or auth endpoint failures with human-readable messages.

#### Scenario: Invalid credentials feedback
- **WHEN** backend mengembalikan respons gagal autentikasi
- **THEN** pengguna melihat toast error yang ramah tanpa kehilangan data input yang sudah diketik
