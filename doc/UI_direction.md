# KKYF Portal v2 UI Direction

This document records the visual direction for the v2 app and should guide future UI work, especially Phase 5 Basic Dashboard.

## Design Inspiration

Use a sleek, soft, modern admin-dashboard style inspired by the provided Donezo dashboard reference:

- soft white cards on a warm light background
- calm KKYF green as the primary accent
- compact left navigation with icons
- clean page headers with short supporting text
- rounded but restrained 8px corners
- subtle shadows and low-contrast borders
- clear primary, secondary, and warning actions
- generous spacing without oversized marketing-style sections

## Dashboard Direction For Phase 5

Phase 5 dashboard cards should follow the same visual language:

- metric cards in a responsive grid
- one primary green summary card
- quiet white secondary cards
- icon/action controls in the top right where useful
- soft status pills for active/inactive/warning states
- no decorative blobs, no heavy gradients, no one-note purple/blue theme

## Responsive Rules

- Desktop uses the left sidebar.
- Tablet and mobile use a hamburger menu with an off-canvas sidebar.
- Content must not overflow horizontally.
- Forms collapse to one column on mobile.
- Long names, emails, file names, and button text must wrap or truncate cleanly.
- Headings should stay clean and compact.

## Interaction Rules

- Use Lucide icons for navigation and clear actions.
- Use SweetAlert2 for success/error feedback, with server-rendered messages as fallback.
- Keep destructive actions visually distinct with warning color.
- Avoid adding new business features during UI-only passes.

