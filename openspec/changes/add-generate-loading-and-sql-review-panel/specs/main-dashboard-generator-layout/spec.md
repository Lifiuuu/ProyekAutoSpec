## MODIFIED Requirements

### Requirement: MainDashboard SHALL render two-panel layout
The system SHALL provide a MainDashboard component that renders a left Sidebar and a right main content area in a clear dashboard layout, while preserving space in the main content flow for post-generate SQL review.

#### Scenario: Sidebar and main content are both visible
- **WHEN** user opens the MainDashboard view
- **THEN** the interface shows a Sidebar region on the left and a primary content region on the right.

#### Scenario: Layout supports review flow after generate
- **WHEN** user triggers Generate and AI processing completes
- **THEN** the main content region keeps a consistent structure and can render SQL Review Panel without breaking sidebar visibility at desktop breakpoints.

### Requirement: Generate button SHALL trigger onGenerate handler
The main content area SHALL include a Generate button styled with color #456882 that invokes onGenerate when clicked, and the button behavior SHALL reflect loading state while generation is in progress.

#### Scenario: Generate action invokes handler
- **WHEN** user clicks the Generate button
- **THEN** the onGenerate function is called.

#### Scenario: Generate button behavior during loading
- **WHEN** `isLoading` is true
- **THEN** the Generate button indicates processing state and blocks repeated trigger until the ongoing generation finishes.
