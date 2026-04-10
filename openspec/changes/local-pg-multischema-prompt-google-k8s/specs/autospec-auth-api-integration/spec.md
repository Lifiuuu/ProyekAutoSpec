## MODIFIED Requirements

### Requirement: Google sign-in configuration SHALL use deployment environment credentials
The system SHALL read Google OAuth credentials from runtime configuration and support the provided deployment values for client id and client secret.

#### Scenario: Google OAuth is configured by environment
- **WHEN** deployment environment is provisioned
- **THEN** backend auth reads `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` from secrets/env and enables Google sign-in flow

### Requirement: Google credential values SHALL be supplied via secrets management
The system SHALL treat Google client id and client secret as sensitive configuration and SHALL not require plaintext embedding in source code or public manifest examples.

#### Scenario: Secret-safe deployment configuration
- **WHEN** Kubernetes manifests or deployment docs are prepared
- **THEN** Google OAuth values are injected through secret resources or equivalent secret manager, not hardcoded in tracked files
