## ADDED Requirements

### Requirement: Kubernetes manifests SHALL target hackathon namespace and domain
The system SHALL configure Kubernetes deployment manifests to target namespace `semute` and ingress host `semute.hackathon.sev-2.com`.

#### Scenario: Deployment uses team namespace and host
- **WHEN** manifests are applied to cluster
- **THEN** resources are created in namespace `semute` and application ingress resolves at `https://semute.hackathon.sev-2.com`

### Requirement: Cluster runtime DB configuration SHALL match team PostgreSQL credentials
The system SHALL support cluster runtime DB settings aligned with provided team endpoint (`103.185.52.138:1185`) and database name `semute`, while credentials are injected through secrets.

#### Scenario: App connects to team database in cluster
- **WHEN** pod starts in hackathon cluster
- **THEN** runtime env points to the team PostgreSQL host/port/database and reads username/password from secret

### Requirement: Deployment artifacts SHALL keep secret examples sanitized
The system SHALL ensure tracked Kubernetes secret examples contain placeholder values only and SHALL not include real credentials, API keys, tokens, or passwords.

#### Scenario: Repository secret example review
- **WHEN** secret example manifest is inspected
- **THEN** all sensitive fields use placeholders and deployment docs instruct injecting real values at deploy time
