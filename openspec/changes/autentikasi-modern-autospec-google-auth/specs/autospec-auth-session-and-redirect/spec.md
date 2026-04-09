## ADDED Requirements

### Requirement: Successful authentication SHALL redirect user to Main Dashboard
The system SHALL redirect authenticated users directly to Main Dashboard immediately after login success (email/password or Google).

#### Scenario: Redirect after login success
- **WHEN** backend mengembalikan autentikasi sukses
- **THEN** sistem mengarahkan pengguna ke halaman Main Dashboard tanpa langkah manual tambahan

### Requirement: Auth session SHALL persist across page refresh
The system SHALL persist authentication token state securely in browser storage and restore login status when the page is refreshed.

#### Scenario: Session restored on refresh
- **WHEN** pengguna sudah login lalu melakukan refresh halaman
- **THEN** sistem membaca token tersimpan dan mempertahankan status login tanpa meminta login ulang

### Requirement: Unauthenticated access SHALL be routed to auth flow
The system SHALL protect dashboard access by routing unauthenticated users to Login page until valid auth session is available.

#### Scenario: Dashboard guarded for anonymous user
- **WHEN** pengguna tanpa token valid membuka route dashboard
- **THEN** sistem mengalihkan pengguna ke halaman Login
