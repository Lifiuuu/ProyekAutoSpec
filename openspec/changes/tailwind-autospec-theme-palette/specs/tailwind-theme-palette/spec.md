## ADDED Requirements

### Requirement: AutoSpec color tokens SHALL be defined as global CSS variables
The system SHALL define global CSS variables for the AutoSpec palette in the active global stylesheet, including `--background`, `--primary`, `--secondary`, `--accent`, and `--text` with values equivalent to `#1E1E1E`, `#1B3C53`, `#234C6A`, `#456882`, and `#F7F8F0`.

#### Scenario: CSS variables are available globally
- **WHEN** the frontend stylesheet is loaded by the application
- **THEN** the CSS variables `--background`, `--primary`, `--secondary`, `--accent`, and `--text` are defined in global scope and can be consumed by styles.

### Requirement: Tailwind color utilities SHALL ma  p to AutoSpec palette tokens
The Tailwind configuration SHALL expose color utilities named `background`, `primary`, `secondary`, `accent`, and `text` mapped to the global CSS variables so developers can use classes such as `bg-primary`, `text-accent`, and `bg-background` consistently.

#### Scenario: Tailwind class names resolve to AutoSpec colors
- **WHEN** a developer applies classes `bg-primary` and `text-accent` in a view/component
- **THEN** the generated CSS uses the corresponding AutoSpec token values from the configured palette.

### Requirement: Tailwind opacity utilities SHALL remain compatible with mapped colors
The Tailwind color mapping SHALL support opacity modifiers for mapped tokens so utility classes such as `bg-primary/80` and `text-accent/70` remain functional.

#### Scenario: Opacity modifier works with token-based colors
- **WHEN** a developer applies class `bg-primary/80`
- **THEN** the generated style uses the `primary` token with the requested opacity rather than falling back to an invalid or unmapped color.
