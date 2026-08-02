# KKYF Portal — Component Library
### Every reusable UI piece, defined once. Reference these by name in build prompts instead of re-describing them — never invent a new variant of an existing component.

All tokens referenced below are defined in `DESIGN_SYSTEM.md`. Every component works in both light and dark mode automatically because it only uses semantic color classes (`bg-primary`, not `bg-green-600`).

---

## Buttons

### Primary Button (Check-in, Save, Submit, Approve)
```html
<button class="min-h-[44px] px-5 py-2.5 rounded-md bg-primary text-on-primary text-label-lg
               shadow-card active:scale-[0.98] motion-safe:transition-transform
               disabled:opacity-40 disabled:pointer-events-none">
  Check In
</button>
```

### Secondary Button (Cancel, secondary actions)
```html
<button class="min-h-[44px] px-5 py-2.5 rounded-md bg-surface-container text-on-surface
               border border-outline-variant text-label-lg active:scale-[0.98]
               motion-safe:transition-transform">
  Cancel
</button>
```

### Destructive Button (Deactivate, Reject, Delete)
```html
<button class="min-h-[44px] px-5 py-2.5 rounded-md bg-error text-on-error text-label-lg
               active:scale-[0.98] motion-safe:transition-transform">
  Deactivate
</button>
```

### Icon Button (nav icons, click-to-call trigger)
```html
<button class="w-11 h-11 flex items-center justify-center rounded-full
               hover:bg-surface-container active:scale-95 motion-safe:transition-transform">
  <i data-lucide="phone" class="w-5 h-5 text-primary"></i>
</button>
```

Rules: buttons are always `rounded-md` (12px), always ≥44px tall, always use `active:scale-[0.98]` for tap feedback wrapped in `motion-safe:`. Never build a one-off button style outside these three variants.

---

## Inputs

```html
<div>
  <label class="text-label-sm text-on-surface-variant uppercase">Full Name</label>
  <input type="text"
    class="mt-1 w-full min-h-[44px] rounded-md bg-surface-container-low px-3.5 py-2.5
           text-body-lg text-on-surface border-b-2 border-transparent
           focus:outline-none focus:border-primary focus:bg-surface-lowest
           placeholder:text-on-surface-variant/60">
</div>
```

- Default state: light gray surface (`bg-surface-container-low`), **no border**.
- Focus state: 2px primary bottom-border + surface lifts to `surface-lowest`.
- Error state: swap `focus:border-primary` behavior for a permanent `border-error` + a `text-error text-body-sm` helper line beneath.
- Select dropdowns and textareas use the identical recipe.

---

## Cards

### Member Card (list rows in members.php / check-in search results)
```html
<div class="bg-surface-lowest rounded-lg p-4 flex items-center gap-3 shadow-card">
  <div class="w-11 h-11 rounded-full bg-primary-container text-on-primary-container
              flex items-center justify-center text-label-lg shrink-0">JD</div>
  <div class="min-w-0 flex-1">
    <div class="text-title-md text-on-surface truncate">Jane Doe</div>
    <div class="text-body-sm text-on-surface-variant">Joined Mar 2026 · Worker</div>
  </div>
  <!-- trailing: status badge and/or chevron -->
  <i data-lucide="chevron-right" class="w-5 h-5 text-on-surface-variant shrink-0"></i>
</div>
```
Name uses `title-md`; secondary line (join date, ID, occupation) uses `body-sm text-on-surface-variant`. Border alternative to shadow when cards are densely stacked: `border border-outline-variant` instead of `shadow-card`.

### Standard Card (generic container)
`bg-surface-lowest rounded-lg p-4 shadow-card` — this is the default wrapper for any dashboard section, form panel, or list container.

### Featured/Hero Widget (dashboard headline stat)
`bg-surface-lowest rounded-xl p-5 shadow-card` — 24px radius, used only for the one or two most important dashboard numbers (e.g. total members, today's check-ins), never for routine content.

---

## Status Badges (pill-shaped, `label-sm` text)

| Status | Classes |
|---|---|
| Pending | `bg-tertiary text-on-tertiary` |
| Called | `bg-secondary text-on-secondary` |
| Converted | `bg-primary text-on-primary` |
| Not Returning | `bg-error text-on-error` |
| Active (member status) | `bg-primary-container text-on-primary-container` |
| Inactive (member status) | `bg-surface-container-high text-on-surface-variant` |
| First-Timer | `bg-secondary-container text-on-secondary-container` |

```html
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-label-sm bg-primary text-on-primary">
  Converted
</span>
```

All badges: `rounded-full px-2.5 py-1 text-label-sm`. Never introduce a new status color outside this table — if a new status is added to the app, pick the closest semantic match (positive→primary, neutral→tertiary, needs-attention→secondary, negative→error).

---

## Navigation

### Desktop Sidebar
`bg-secondary text-on-secondary w-64 fixed inset-y-0 left-0` — nav links use `text-on-secondary/85`, hover `bg-on-secondary/10`, active link `bg-on-secondary/15 text-on-secondary font-medium`.

### Mobile Bottom Tab Bar
`bg-secondary text-on-secondary fixed bottom-0 inset-x-0 flex items-center justify-around py-2` — each tab is an icon (Lucide, `w-5 h-5`) + `text-label-sm` beneath, inactive at `text-on-secondary/60`, active at `text-on-secondary`.

Both use the **secondary** (indigo) token per the brand direction — secondary is the branding/navigation anchor color, primary is reserved for actions.

---

## Modal / Bottom Sheet
*(used for: Add Tent, Add Member inline during check-in, first-timer quick-add)*

```html
<div class="fixed inset-0 bg-inverse-surface/40 backdrop-blur-md flex items-end md:items-center
            justify-center z-50">
  <div class="bg-surface-lowest w-full md:max-w-md rounded-t-xl md:rounded-xl shadow-elevated
              p-5 md:p-6">
    <!-- content -->
  </div>
</div>
```
Mobile: slides up as a bottom sheet (`rounded-t-xl`, anchored to `items-end`). Desktop: centered modal (`rounded-xl`, `items-center`). Scrim uses `inverse-surface` at 40% opacity + `backdrop-blur-md`, per the elevation spec.

---

## Toast (Notyf)

Configure Notyf's success/error colors to pull from the token RGB values so toasts match the theme instead of Notyf's defaults:

```js
const notyf = new Notyf({
  duration: 3500,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: 'rgb(var(--color-primary))', icon: false },
    { type: 'error', background: 'rgb(var(--color-error))', icon: false },
  ]
});
```

---

## Dashboard Widget (single-metric stat card)

```html
<div class="bg-surface-lowest rounded-xl p-5 shadow-card">
  <div class="text-label-sm text-on-surface-variant uppercase mb-1">Active Members</div>
  <div class="text-display-lg text-on-surface">450</div>
  <div class="mt-2 inline-flex items-center gap-1 text-body-sm text-primary">
    <i data-lucide="trending-up" class="w-3.5 h-3.5"></i> +12% this month
  </div>
</div>
```
The number is always `display-lg`. The trend line is optional — only include it where a real week/month comparison exists; never fabricate a trend indicator.

---

## Empty States

Every list/table page needs an empty state — never leave a bare blank area.

```html
<div class="text-center py-12 px-6">
  <i data-lucide="users" class="w-8 h-8 text-on-surface-variant/50 mx-auto mb-3"></i>
  <p class="text-body-lg text-on-surface-variant mb-4">No members yet in this tent.</p>
  <button class="...">Add Member</button>
</div>
```
Copy pattern: state what's missing in plain terms, then offer the one action that fixes it. No apologetic tone, no filler.

---

## Dark Mode Toggle Control
*(place in the sidebar footer / mobile settings)*

```html
<button x-data @click="$store.theme.toggle()" class="w-11 h-11 flex items-center justify-center rounded-full hover:bg-on-secondary/10">
  <i data-lucide="sun" class="w-5 h-5" x-show="$store.theme.dark" x-cloak></i>
  <i data-lucide="moon" class="w-5 h-5" x-show="!$store.theme.dark" x-cloak></i>
</button>
```
Implementation of `$store.theme` is specified in `TECH_SPEC.md` §5 — do not reimplement theme logic per-page.
