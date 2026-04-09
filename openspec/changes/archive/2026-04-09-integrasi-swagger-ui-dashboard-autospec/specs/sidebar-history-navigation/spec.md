## ADDED Requirements

### Requirement: Sidebar SHALL provide quick access to API documentation
The system SHALL add a sidebar action labeled `Lihat Dokumentasi API` so users can open the interactive Swagger documentation from the sidebar or result area.

#### Scenario: Sidebar action is available after API generation
- **WHEN** the dashboard has generated OpenAPI content
- **THEN** the sidebar shows a quick access action for opening the API documentation panel

### Requirement: Sidebar documentation action SHALL trigger the same viewer as the dashboard entry point
The system SHALL route the sidebar action to the same `SwaggerDocs.jsx` viewer used by the main dashboard so the documentation experience stays consistent.

#### Scenario: Both entry points open the same viewer
- **WHEN** the user clicks the sidebar action `Lihat Dokumentasi API`
- **THEN** the same documentation panel opens with the current `specData` context instead of a separate or duplicated viewer
