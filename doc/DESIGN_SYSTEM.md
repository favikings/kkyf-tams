# KKYF Portal — Design System
### Reference spec for every visual decision. Do not invent colors, spacing, or type sizes outside this document.

Source: Ken Katas Youth Foundation Material 3 token export (`DESIGN.md`, supplied by the client). This document translates it into a build-ready form: exact CSS variables for light **and** dark mode, a Tailwind config that reads them, and the semantic rules for when to use each token.

---

## 1. How theming works in this app

No build step (Tailwind via CDN), so theming is done with **CSS custom properties + Tailwind's `darkMode: 'class'` strategy** — not two separate Tailwind configs.

- All colors are defined as CSS variables (`--color-*`) in `:root` (light values) and overridden inside a `.dark` selector (dark values).
- Tailwind's `tailwind.config` maps semantic color names (e.g. `primary`, `surface`) to those variables using the `rgb(var(--x) / <alpha-value>)` pattern, so Tailwind opacity utilities (`bg-primary/50`) still work.
- Dark mode is toggled by adding/removing the `dark` class on `<html>`, persisted in `localStorage`, defaulting to the user's `prefers-color-scheme`.
- **Every page must use semantic class names** (`bg-surface`, `text-on-surface`, `bg-primary`) — never raw hex values or arbitrary Tailwind colors (`bg-green-600`, `text-gray-800`, etc.). If a color isn't in this token list, it doesn't belong in this app.

---

## 2. CSS Variables — paste into a single stylesheet, loaded on every page

```css
:root {
  /* surfaces */
  --color-background: 248 249 250;
  --color-on-background: 25 28 29;
  --color-surface: 248 249 250;
  --color-surface-dim: 217 218 219;
  --color-surface-bright: 248 249 250;
  --color-surface-container-lowest: 255 255 255;
  --color-surface-container-low: 243 244 245;
  --color-surface-container: 237 238 239;
  --color-surface-container-high: 231 232 233;
  --color-surface-container-highest: 225 227 228;
  --color-surface-variant: 225 227 228;
  --color-on-surface: 25 28 29;
  --color-on-surface-variant: 62 74 62;
  --color-inverse-surface: 46 49 50;
  --color-inverse-on-surface: 240 241 242;
  --color-outline: 110 122 109;
  --color-outline-variant: 189 202 186;

  /* primary (green — action / check-in / success) */
  --color-primary: 0 110 44;
  --color-on-primary: 255 255 255;
  --color-primary-container: 52 168 83;
  --color-on-primary-container: 0 53 17;
  --color-inverse-primary: 109 221 129;
  --color-surface-tint: 0 110 44;

  /* secondary (indigo — branding / nav / headers) */
  --color-secondary: 83 67 214;
  --color-on-secondary: 255 255 255;
  --color-secondary-container: 108 95 240;
  --color-on-secondary-container: 255 251 255;

  /* tertiary (neutral gray — supporting UI) */
  --color-tertiary: 93 94 97;
  --color-on-tertiary: 255 255 255;
  --color-tertiary-container: 146 147 149;
  --color-on-tertiary-container: 42 44 46;

  /* error */
  --color-error: 186 26 26;
  --color-on-error: 255 255 255;
  --color-error-container: 255 218 214;
  --color-on-error-container: 147 0 10;

  /* fixed tokens — identical in light & dark, for elements that must not flip */
  --color-primary-fixed: 137 250 155;
  --color-primary-fixed-dim: 109 221 129;
  --color-on-primary-fixed: 0 33 8;
  --color-on-primary-fixed-variant: 0 83 32;
  --color-secondary-fixed: 228 223 255;
  --color-secondary-fixed-dim: 197 192 255;
  --color-on-secondary-fixed: 20 0 103;
  --color-on-secondary-fixed-variant: 60 39 192;
  --color-tertiary-fixed: 226 226 229;
  --color-tertiary-fixed-dim: 198 198 201;
  --color-on-tertiary-fixed: 26 28 30;
  --color-on-tertiary-fixed-variant: 69 71 73;
}

.dark {
  /* surfaces — derived per M3 dark-scheme mapping */
  --color-background: 16 20 19;
  --color-on-background: 225 227 224;
  --color-surface: 16 20 19;
  --color-surface-dim: 16 20 19;
  --color-surface-bright: 55 59 57;
  --color-surface-container-lowest: 11 15 14;
  --color-surface-container-low: 25 28 27;
  --color-surface-container: 29 32 31;
  --color-surface-container-high: 40 43 41;
  --color-surface-container-highest: 51 54 52;
  --color-surface-variant: 65 74 65;
  --color-on-surface: 225 227 224;
  --color-on-surface-variant: 192 204 190;
  --color-inverse-surface: 225 227 224;
  --color-inverse-on-surface: 25 28 29;
  --color-outline: 136 147 136;
  --color-outline-variant: 62 74 62;

  /* primary — dark scheme uses the "fixed-dim" tone as the active primary */
  --color-primary: 109 221 129;
  --color-on-primary: 0 33 8;
  --color-primary-container: 0 83 32;
  --color-on-primary-container: 137 250 155;
  --color-inverse-primary: 0 110 44;
  --color-surface-tint: 109 221 129;

  /* secondary */
  --color-secondary: 197 192 255;
  --color-on-secondary: 20 0 103;
  --color-secondary-container: 60 39 192;
  --color-on-secondary-container: 228 223 255;

  /* tertiary */
  --color-tertiary: 198 198 201;
  --color-on-tertiary: 26 28 30;
  --color-tertiary-container: 69 71 73;
  --color-on-tertiary-container: 226 226 229;

  /* error — standard M3 baseline dark-error tones */
  --color-error: 255 180 171;
  --color-on-error: 105 0 5;
  --color-error-container: 147 0 10;
  --color-on-error-container: 255 218 214;

  /* fixed tokens — unchanged from :root, repeated here for clarity */
  --color-primary-fixed: 137 250 155;
  --color-primary-fixed-dim: 109 221 129;
  --color-on-primary-fixed: 0 33 8;
  --color-on-primary-fixed-variant: 0 83 32;
  --color-secondary-fixed: 228 223 255;
  --color-secondary-fixed-dim: 197 192 255;
  --color-on-secondary-fixed: 20 0 103;
  --color-on-secondary-fixed-variant: 60 39 192;
  --color-tertiary-fixed: 226 226 229;
  --color-tertiary-fixed-dim: 198 198 201;
  --color-on-tertiary-fixed: 26 28 30;
  --color-on-tertiary-fixed-variant: 69 71 73;
}
```

> **Note on the dark palette:** the client's design file only specified light-mode hex values. The dark values above are derived by applying Material 3's standard light↔dark role-swap rules (dark-scheme `primary` = light-scheme `primary-fixed-dim`, dark-scheme `surface` ≈ light-scheme `inverse-surface`, etc.), using the `-fixed` tokens in the source file as anchors since those are scheme-invariant by design. Error tones use the M3 baseline dark-error set, since the light error values are the M3 baseline red. If an official Material Theme Builder dark export becomes available later, replace the `.dark {}` block with it directly — nothing else in the app needs to change.

---

## 3. Tailwind Config (paste into the inline `tailwind.config` script tag)

```js
tailwind.config = {
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        background: 'rgb(var(--color-background) / <alpha-value>)',
        'on-background': 'rgb(var(--color-on-background) / <alpha-value>)',
        surface: 'rgb(var(--color-surface) / <alpha-value>)',
        'surface-dim': 'rgb(var(--color-surface-dim) / <alpha-value>)',
        'surface-bright': 'rgb(var(--color-surface-bright) / <alpha-value>)',
        'surface-lowest': 'rgb(var(--color-surface-container-lowest) / <alpha-value>)',
        'surface-low': 'rgb(var(--color-surface-container-low) / <alpha-value>)',
        'surface-container': 'rgb(var(--color-surface-container) / <alpha-value>)',
        'surface-high': 'rgb(var(--color-surface-container-high) / <alpha-value>)',
        'surface-highest': 'rgb(var(--color-surface-container-highest) / <alpha-value>)',
        'surface-variant': 'rgb(var(--color-surface-variant) / <alpha-value>)',
        'on-surface': 'rgb(var(--color-on-surface) / <alpha-value>)',
        'on-surface-variant': 'rgb(var(--color-on-surface-variant) / <alpha-value>)',
        'inverse-surface': 'rgb(var(--color-inverse-surface) / <alpha-value>)',
        'inverse-on-surface': 'rgb(var(--color-inverse-on-surface) / <alpha-value>)',
        outline: 'rgb(var(--color-outline) / <alpha-value>)',
        'outline-variant': 'rgb(var(--color-outline-variant) / <alpha-value>)',

        primary: 'rgb(var(--color-primary) / <alpha-value>)',
        'on-primary': 'rgb(var(--color-on-primary) / <alpha-value>)',
        'primary-container': 'rgb(var(--color-primary-container) / <alpha-value>)',
        'on-primary-container': 'rgb(var(--color-on-primary-container) / <alpha-value>)',
        'inverse-primary': 'rgb(var(--color-inverse-primary) / <alpha-value>)',

        secondary: 'rgb(var(--color-secondary) / <alpha-value>)',
        'on-secondary': 'rgb(var(--color-on-secondary) / <alpha-value>)',
        'secondary-container': 'rgb(var(--color-secondary-container) / <alpha-value>)',
        'on-secondary-container': 'rgb(var(--color-on-secondary-container) / <alpha-value>)',

        tertiary: 'rgb(var(--color-tertiary) / <alpha-value>)',
        'on-tertiary': 'rgb(var(--color-on-tertiary) / <alpha-value>)',
        'tertiary-container': 'rgb(var(--color-tertiary-container) / <alpha-value>)',
        'on-tertiary-container': 'rgb(var(--color-on-tertiary-container) / <alpha-value>)',

        error: 'rgb(var(--color-error) / <alpha-value>)',
        'on-error': 'rgb(var(--color-on-error) / <alpha-value>)',
        'error-container': 'rgb(var(--color-error-container) / <alpha-value>)',
        'on-error-container': 'rgb(var(--color-on-error-container) / <alpha-value>)',

        'primary-fixed': 'rgb(var(--color-primary-fixed) / <alpha-value>)',
        'primary-fixed-dim': 'rgb(var(--color-primary-fixed-dim) / <alpha-value>)',
        'on-primary-fixed': 'rgb(var(--color-on-primary-fixed) / <alpha-value>)',
        'on-primary-fixed-variant': 'rgb(var(--color-on-primary-fixed-variant) / <alpha-value>)',
      },
      fontFamily: {
        display: ['"Geist"', 'ui-sans-serif', 'system-ui'],
        body: ['"Inter"', 'ui-sans-serif', 'system-ui'],
      },
      borderRadius: {
        sm: '0.25rem',
        DEFAULT: '0.5rem',
        md: '0.75rem',
        lg: '1rem',
        xl: '1.5rem',
        full: '9999px',
      },
      boxShadow: {
        card: '0 4px 12px 0 rgb(0 0 0 / 0.04)',
        elevated: '0 8px 24px 0 rgb(0 0 0 / 0.10)',
      },
      spacing: {
        18: '4.5rem',
      },
    },
  },
}
```

Load order in `header.php`: Google Fonts (Geist + Inter) → the `:root`/`.dark` variable stylesheet above → Tailwind CDN script → the `tailwind.config` script. Variables must be defined before Tailwind's CDN script runs.

---

## 4. Typography Scale

| Token | Family | Size | Weight | Line height | Tracking | Use |
|---|---|---|---|---|---|---|
| `display-lg` | Geist | 48px | 700 | 56px | -0.02em | Dashboard hero stat (e.g. member count) |
| `headline-lg` | Geist | 32px | 600 | 40px | -0.01em | Page titles, desktop |
| `headline-lg-mobile` | Geist | 28px | 600 | 36px | normal | Page titles, mobile |
| `title-md` | Geist | 20px | 600 | 28px | normal | Card titles, section headers, member name on card |
| `body-lg` | Inter | 16px | 400 | 24px | normal | Default body copy, form labels' input text |
| `body-sm` | Inter | 14px | 400 | 20px | normal | Secondary/meta text (ID, join date, timestamps) |
| `label-lg` | Geist | 14px | 600 | 20px | 0.02em | Button labels |
| `label-sm` | Geist | 12px | 500 | 16px | 0.04em | Badge text, form field captions |

Tailwind utility recipes (since these aren't default Tailwind sizes, apply directly as arbitrary values or a small set of helper classes in a shared stylesheet):

```css
.text-display-lg { font-family: theme('fontFamily.display'); font-size: 48px; font-weight: 700; line-height: 56px; letter-spacing: -0.02em; }
.text-headline-lg { font-family: theme('fontFamily.display'); font-size: 32px; font-weight: 600; line-height: 40px; letter-spacing: -0.01em; }
.text-headline-lg-mobile { font-family: theme('fontFamily.display'); font-size: 28px; font-weight: 600; line-height: 36px; }
.text-title-md { font-family: theme('fontFamily.display'); font-size: 20px; font-weight: 600; line-height: 28px; }
.text-body-lg { font-family: theme('fontFamily.body'); font-size: 16px; font-weight: 400; line-height: 24px; }
.text-body-sm { font-family: theme('fontFamily.body'); font-size: 14px; font-weight: 400; line-height: 20px; }
.text-label-lg { font-family: theme('fontFamily.display'); font-size: 14px; font-weight: 600; line-height: 20px; letter-spacing: 0.02em; }
.text-label-sm { font-family: theme('fontFamily.display'); font-size: 12px; font-weight: 500; line-height: 16px; letter-spacing: 0.04em; }
```

Use `headline-lg-mobile` below the `md:` breakpoint and `headline-lg` at `md:` and above for all page titles (swap via Tailwind responsive classes, not JS).

---

## 5. Spacing, Radii, Layout

| Token | Value | Use |
|---|---|---|
| `xs` | 4px | Icon-to-label gaps |
| `sm` | 8px | Tight internal gaps |
| `md` | 16px | Default gap between stacked elements/widgets, default card padding |
| `lg` | 24px | Section spacing |
| `xl` | 32px | Page-level spacing between major blocks |
| `margin-mobile` | 20px | Side margin on mobile viewports |
| `margin-desktop` | 40px | Side margin on desktop viewports |

- **Touch targets:** every interactive element (buttons, list rows, nav items) must be **at least 44px tall**.
- **Card padding:** 16px (`p-4`) as default, 20px (`p-5`) for cards showing dense member data.
- **Vertical rhythm between dashboard widgets:** 16px (`gap-4` / `space-y-4`).

| Radius token | Value | Use |
|---|---|---|
| `sm` | 0.25rem (4px) | Small chips, inline elements |
| `DEFAULT` | 0.5rem (8px) | Rarely used directly — prefer named sizes below |
| `md` | 0.75rem (12px) | **Buttons and inputs** |
| `lg` | 1rem (16px) | **Standard cards** |
| `xl` | 1.5rem (24px) | **Featured/hero dashboard widgets** |
| `full` | 9999px | **Badges/status pills** |

---

## 6. Elevation & Depth

| Layer | Treatment |
|---|---|
| Base (app background) | `bg-background` — flat, no shadow |
| Surface (cards) | `bg-surface-lowest` (white in light) + `shadow-card` (blur 12px, y 4px, 4% opacity) |
| Active/pressable (Check-in button, tap targets) | Deeper shadow on rest state; `active:scale-[0.98]` transform on press for tactile feedback |
| Overlays (modals, bottom sheets) | `backdrop-blur-md` on the scrim + `shadow-elevated` on the sheet/modal itself |

Reduced motion: wrap the `active:scale-[0.98]` and any entrance transitions in a `motion-safe:` Tailwind variant so users with `prefers-reduced-motion` don't get the transform.

---

## 7. Color Usage Rules (semantic, not literal)

| Situation | Token |
|---|---|
| Primary call-to-action (Check-in, Save, Submit, Approve) | `bg-primary text-on-primary` |
| Positive/success state, growth indicator, "Converted" status | `primary` family |
| Branding surfaces, sidebar/header background, nav icons | `secondary` family |
| Supporting/neutral UI, secondary buttons, "Pending" status | `tertiary` family |
| Destructive action, validation error, "Not Returning" status | `error` family |
| Page/app background | `bg-background` |
| Card surface | `bg-surface-lowest` (or `surface-container-*` for nested/layered cards) |
| Primary text | `text-on-surface` |
| Secondary/meta text | `text-on-surface-variant` |
| Dividers, input borders (default state) | `border-outline-variant` |
| Focus ring / active input border | `border-primary` (2px, per input spec below) |

---
*This document is the single source of truth for visual tokens. `COMPONENTS.md` builds directly on top of it — component specs never redefine a color or size, they only reference these tokens by name.*
