---
name: KKYF Membership Portal v2
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#3d4a3c'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#6d7b6a'
  outline-variant: '#bccbb8'
  surface-tint: '#006e24'
  primary: '#006e24'
  on-primary: '#ffffff'
  primary-container: '#00a83b'
  on-primary-container: '#00320c'
  inverse-primary: '#58e06b'
  secondary: '#526258'
  on-secondary: '#ffffff'
  secondary-container: '#d2e4d7'
  on-secondary-container: '#56665c'
  tertiary: '#005ac2'
  on-tertiary: '#ffffff'
  tertiary-container: '#4d8eff'
  on-tertiary-container: '#00285d'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#76fe84'
  primary-fixed-dim: '#58e06b'
  on-primary-fixed: '#002106'
  on-primary-fixed-variant: '#005319'
  secondary-fixed: '#d5e7da'
  secondary-fixed-dim: '#b9cbbe'
  on-secondary-fixed: '#101f17'
  on-secondary-fixed-variant: '#3b4a41'
  tertiary-fixed: '#d8e2ff'
  tertiary-fixed-dim: '#adc6ff'
  on-tertiary-fixed: '#001a42'
  on-tertiary-fixed-variant: '#004395'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  2xl: 48px
  container-max: 1280px
  gutter: 20px
---

## Brand & Style
The design system focuses on the dual themes of **growth** and **governance**. It is tailored for the KKYF (Ken Katas Youth Foundation) Membership Portal, ensuring that the interface feels like a professional management tool while maintaining a community-centric soul.

The aesthetic is **Modern Corporate**, blending high-utility PWA patterns with a welcoming, accessible atmosphere. It prioritizes clarity for data-heavy administrative tasks—such as membership tracking and reporting—while using vibrant color accents to celebrate the foundation's impact on youth. The emotional response should be one of confidence, reliability, and progress.

## Colors
The palette is rooted in the foundation’s identity. 

- **Primary (#00A83B):** Used for key actions, growth indicators, and active states. It represents the energy of the youth.
- **Deep Forest (#112018):** Used for sidebars, headers, and primary text. It provides a grounded, authoritative contrast to the vibrant primary green.
- **Surface (#F8FAFC):** A clean, cool-tinted light gray background that reduces eye strain during long administrative sessions.

**Role-Based UI Cues:**
- **Super Admin:** Utilizes deep forest accents in the global navigation bar to signify top-level authority.
- **Tent Admin:** Uses lighter borders and localized primary green headers to focus on specific community sections.

## Typography
This design system utilizes **Inter** for its exceptional legibility in data-dense environments. 

- **Headlines:** Set with tight tracking and semi-bold weights to create a sense of structure.
- **Data Tables:** Use `label-md` for row content and `label-sm` (uppercase) for column headers to maximize horizontal space.
- **Readability:** High contrast is maintained by using Deep Forest (#112018) for all primary body text on light backgrounds.

## Layout & Spacing
The layout follows a **Fluid Grid** model with a focus on dashboard clarity.

- **Grid:** A 12-column system for desktop, collapsing to 4 columns on mobile. 
- **Margins:** 32px on desktop to give data "room to breathe," 16px on mobile for maximum content area.
- **Rhythm:** An 8px linear scale ensures consistent vertical rhythm between components.
- **Density:** In report views, padding is reduced to `sm` (8px), while in profile or marketing views, padding is increased to `lg` (24px) to enhance the welcoming feel.

## Elevation & Depth
This design system uses **Tonal Layers** supplemented by **Ambient Shadows** to create a structured hierarchy without visual clutter.

- **Level 0 (Background):** #F8FAFC. The canvas for all content.
- **Level 1 (Cards/Tables):** Pure White (#FFFFFF) with a thin 1px border (#E2E8F0).
- **Level 2 (Dropdowns/Modals):** Pure White with a soft, diffused shadow (0px 10px 15px -3px rgba(17, 32, 24, 0.1)).
- **Level 3 (Priority Popovers):** Deeper shadows with a slight Deep Forest tint to pull elements forward.

Interactive elements use a subtle lift on hover to signify "pressability."

## Shapes
A "Rounded" shape language is used to soften the professional tone, making the portal feel approachable for young members.

- **Components:** Buttons and input fields use a 0.5rem (8px) radius.
- **Containers:** Large dashboard cards and sections use 1rem (16px) radius to create clear visual groupings.
- **Status Pills:** Fully rounded (pill-shaped) to distinguish them from interactive buttons.

## Components
- **Buttons:** Primary buttons are Solid Green (#00A83B) with white text. Secondary buttons use a Deep Forest (#112018) outline.
- **Cards:** White background, 1px border, 16px padding. Use for membership stats and "Tent" summaries.
- **Data Tables:** Clean rows with light gray dividers. High-contrast text for names and IDs. Use pill-shaped badges for "Active," "Pending," or "Inactive" statuses.
- **Input Fields:** 8px rounded corners, 1px neutral border. Focused state uses a 2px Green (#00A83B) ring.
- **Status Indicators:** Small colored dots or light-tinted background badges used to show the health of a specific Tent or the verification status of a member.
- **Admin Switcher:** A prominent but clean toggle in the sidebar to allow Super Admins to filter views by Tent location without losing global context.