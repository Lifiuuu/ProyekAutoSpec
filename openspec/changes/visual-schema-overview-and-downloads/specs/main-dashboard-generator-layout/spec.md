## MODIFIED Requirements

### Requirement: MainDashboard SHALL render two-panel layout
The system SHALL provide a MainDashboard component that renders a left Sidebar and a right main content area in a clear dashboard layout, and SHALL support rendering Visual Schema Overview and download actions in the right content flow.

#### Scenario: Sidebar and main content are both visible
- **WHEN** user opens the MainDashboard view
- **THEN** the interface shows a Sidebar region on the left and a primary content region on the right.

#### Scenario: Layout supports schema overview and downloads
- **WHEN** schema and artifact data are available
- **THEN** the main content area can show table cards, credentials card, and download buttons without breaking sidebar structure at desktop breakpoints.

### Requirement: Main content SHALL include credentials summary
The main content area SHALL include a Database Credentials card displaying unique username and password for the generated environment.

#### Scenario: Credentials card is visible
- **WHEN** credentials data is present
- **THEN** a dedicated card displays username and password values.
