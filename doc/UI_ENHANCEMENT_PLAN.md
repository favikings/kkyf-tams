# UI Enhancement Plan

This plan outlines a phased UI refinement for the KKYF Membership Portal v2.

## Goal

Improve the product UI so it feels sleeker, calmer, more breathable, and more professional without disrupting working functionality.

## Design Direction

- Reduce visual clutter
- Increase spacing and layout rhythm
- Simplify surfaces and borders
- Use a restrained green accent system
- Improve typography hierarchy
- Make forms and tables easier to scan
- Keep the interface modern, minimal, and functional

## Phase 1: Design Foundation

Goal: create a stable visual system that all screens can share.

- Define spacing scale for page sections, cards, forms, and tables
- Define typography scale for headings, body text, labels, and helper text
- Standardize color tokens for:
  - primary accent
  - background
  - surfaces
  - borders
  - muted text
  - error/success states
- Standardize border radius, shadows, and surface treatments
- Refine button and input styles

Deliverables:

- Updated global CSS tokens
- Reusable visual standards for future pages

## Phase 2: App Shell Cleanup

Goal: improve the global layout before touching individual feature pages.

- Refine top navigation/header spacing
- Improve sidebar or navigation rhythm where applicable
- Standardize content width and inner page padding
- Reduce unnecessary lines, boxes, and heavy separators
- Improve responsive spacing for tablet and mobile

Deliverables:

- Cleaner app shell
- More consistent layout across all screens

## Phase 3: Dashboard Redesign

Goal: improve first impression and reduce dashboard clutter.

- Redesign metric cards with stronger hierarchy
- Increase white space between sections
- Reorder information so the most important content appears first
- Reduce the number of competing panels on first view
- Improve empty and fallback states

Deliverables:

- Cleaner, more polished dashboard experience

## Phase 4: Forms and Inputs

Goal: make create/edit workflows feel lighter and easier to use.

- Increase spacing between fields
- Improve labels, field hints, and validation states
- Standardize focus and hover behavior
- Improve modal form structure
- Make action buttons clearer and more consistent

Deliverables:

- Better member, tent, attendance, and first-timer form UX

## Phase 5: Tables and Data Views

Goal: make data-dense pages more readable.

- Increase row height and internal padding
- Improve column alignment and readability
- Simplify table headers
- Improve filters, search, and action placement
- Add better empty states and loading states

Deliverables:

- Better members, attendance, reports, and admin listing pages

## Phase 6: Auth and Entry Screens

Goal: improve the login and entry experience.

- Refine login screen spacing and structure
- Improve branding balance
- Reduce unnecessary visual noise
- Ensure mobile layout feels intentional

Deliverables:

- More polished login and entry flow

## Phase 7: Final Polish

Goal: make the whole UI feel consistent and production-ready.

- Audit all screens for consistency
- Normalize icons, spacing, and button styles
- Remove leftover legacy visual patterns
- Improve hover, active, and focus states
- Test responsive behavior across screen sizes

Deliverables:

- Unified visual system across the app

## Recommended Implementation Order

1. Phase 1: Design Foundation
2. Phase 2: App Shell Cleanup
3. Phase 3: Dashboard Redesign
4. Phase 4: Forms and Inputs
5. Phase 5: Tables and Data Views
6. Phase 6: Auth and Entry Screens
7. Phase 7: Final Polish

## Best Starting Point

When work resumes, start with:

1. Phase 1
2. Phase 2
3. Phase 3

These three phases will create the biggest visible improvement fastest.

## Notes

- Keep functionality stable while improving visuals
- Prefer system-wide refinements over isolated page-by-page styling hacks
- Avoid introducing design inconsistency between old and updated screens
- Preserve accessibility and mobile usability during redesign
