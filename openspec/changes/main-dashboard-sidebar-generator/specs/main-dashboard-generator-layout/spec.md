## ADDED Requirements

### Requirement: MainDashboard SHALL render two-panel layout
The system SHALL provide a MainDashboard component that renders a left Sidebar and a right main content area in a clear dashboard layout.

#### Scenario: Sidebar and main content are both visible
- **WHEN** user opens the MainDashboard view
- **THEN** the interface shows a Sidebar region on the left and a primary content region on the right.

### Requirement: Sidebar SHALL show AutoSpec logo and generation history list
The Sidebar SHALL display AutoSpec branding at the top and a Histori Generasi list section below it.

#### Scenario: Sidebar content order is correct
- **WHEN** MainDashboard is rendered
- **THEN** the AutoSpec logo appears in the top area and Histori Generasi appears beneath it.

### Requirement: Prompt input section SHALL provide NLP textarea
The main content area SHALL include a large textarea for NLP prompts, using border color #234C6A and placeholder text Bikinin database perpustakaan....

#### Scenario: Prompt box styling and placeholder are present
- **WHEN** MainDashboard loads
- **THEN** the prompt textarea is visible with border #234C6A and placeholder Bikinin database perpustakaan....

### Requirement: Dialect dropdown SHALL enforce availability states
The main content area SHALL include a database dialect dropdown where PostgreSQL is available, and MySQL, MariaDB, and SQLite are disabled with Coming Soon labeling.

#### Scenario: Dialect options display enabled and disabled states
- **WHEN** user opens the dialect dropdown
- **THEN** PostgreSQL is selectable and MySQL, MariaDB, SQLite are disabled and marked as Coming Soon.

### Requirement: Generate button SHALL trigger onGenerate handler
The main content area SHALL include a Generate button styled with color #456882 that invokes onGenerate when clicked.

#### Scenario: Generate action invokes handler
- **WHEN** user clicks the Generate button
- **THEN** the onGenerate function is called.
