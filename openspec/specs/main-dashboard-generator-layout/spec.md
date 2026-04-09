## ADDED Requirements

### Requirement: Main dashboard SHALL expose API documentation entry point
The system SHALL provide a clear action from the main dashboard result area to open the interactive API documentation panel after database generation completes.

#### Scenario: Documentation action appears after generation
- **WHEN** the generation workflow finishes and OpenAPI output is available
- **THEN** the main dashboard shows an action labeled `Lihat Dokumentasi API` or an equivalent documentation entry point

### Requirement: Main dashboard SHALL open documentation without leaving the current workspace
The system SHALL open the API documentation panel in a modal or full-width card so users can inspect Swagger UI without navigating away from the dashboard.

#### Scenario: Documentation opens in-place
- **WHEN** the user activates the documentation entry point from the dashboard
- **THEN** the documentation panel opens within the current dashboard context and remains responsive on desktop and mobile
