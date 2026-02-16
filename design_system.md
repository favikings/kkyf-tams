# design_system.md

## 1. Color Palette (Semantic Naming)
**Design Philosophy:** High-energy, youthful, and accessible. The primary green (`#00BD06`) is vibrant; it acts as the "action" color. The neutral scale is cool-toned to maintain a modern, tech-forward PWA aesthetic.

| Token | Hex Code | Tailwind Class | Usage |
| :--- | :--- | :--- | :--- |
| **Primary** | `#00BD06` | `bg-[#00BD06]` | Primary buttons, active states, key branding elements. |
| **Primary Hover** | `#009605` | `hover:bg-[#009605]` | Interaction state for primary elements. |
| **Secondary** | `#1E293B` | `bg-slate-800` | Navigation bars, sidebars, heavy headings (Deep Slate). |
| **Accent** | `#8B5CF6` | `text-violet-500` | Highlights, "New" badges, creative accents (Complementary energetic purple). |
| **Background** | `#F8FAFC` | `bg-slate-50` | Main application background (Off-white/Cool gray). |
| **Surface** | `#FFFFFF` | `bg-white` | Cards, modals, input fields, tables. |
| **Text Main** | `#0F172A` | `text-slate-900` | H1-H3, primary data, table headers. |
| **Text Muted** | `#64748B` | `text-slate-500` | Meta-data, placeholders, captions. |
| **Success** | `#10B981` | `text-emerald-500` | Attendance confirmed, Action success. |
| **Error** | `#EF4444` | `text-red-500` | Form errors, deletion warnings. |
| **Warning** | `#F59E0B` | `text-amber-500` | Missing data, non-blocking alerts. |

---

## 2. Typography System
**Font Family:**
* **Headings:** `Plus Jakarta Sans` (Google Fonts) – Geometric, friendly, modern.
* **Body/Data:** `Inter` (Google Fonts) – Highly legible for tables and dense lists.

**Scale & Weights:**

| Type Role | Tag | Size | Weight | Tailwind Class |
| :--- | :--- | :--- | :--- | :--- |
| **Page Title** | H1 | 24px / 1.5rem | Bold (700) | `text-2xl font-bold tracking-tight font-sans` |
| **Section Header** | H2 | 20px / 1.25rem | SemiBold (600) | `text-xl font-semibold text-slate-800 font-sans` |
| **Card Title** | H3 | 18px / 1.125rem | Medium (500) | `text-lg font-medium text-slate-900` |
| **Body Default** | p | 16px / 1rem | Regular (400) | `text-base text-slate-600 font-inter` |
| **Table Data** | td | 14px / 0.875rem | Regular (400) | `text-sm text-slate-700 font-inter` |
| **Caption/Label** | small | 12px / 0.75rem | Medium (500) | `text-xs uppercase tracking-wide text-slate-500` |

---

## 3. Component "DNA" (The Rules)
**Visual Style:** Modern iOS/Android PWA aesthetic. "Soft and Round."

### Structure & Shape
* **Border Radius:** Generous rounding to feel friendly and youthful.
    * **Cards/Containers:** `rounded-2xl` (16px).
    * **Buttons:** `rounded-full` (Capsule style).
    * **Inputs:** `rounded-xl` (12px).
    * **Tags/Badges:** `rounded-md` (6px).

### Depth & Elevation
* **Shadows:** Soft, diffused shadows to lift content off the background.
    * **Cards:** `shadow-sm` (Subtle) -> `hover:shadow-md` (Interactive).
    * **Floating Actions (FAB):** `shadow-lg shadow-green-500/30` (Glow effect).
    * **Modals:** `shadow-2xl`.

### Spacing Strategy
* **Density:** Comfortable. Optimized for touch targets (Mobile First).
* **Touch Targets:** Minimum height `h-12` (48px) for all clickable inputs/buttons.
* **Padding:**
    * **Page Container:** `p-4` or `p-6` (Mobile/Desktop).
    * **Card Internal:** `p-5`.
    * **Gap:** `gap-4` standard between grid items.

### Input Field Style
* **Appearance:** Filled style for clear hit-areas.
* **Default:** `bg-slate-100 border-transparent text-slate-900`.
* **Focus:** `ring-2 ring-[#00BD06] bg-white ring-offset-1`.
* **Placeholder:** `text-slate-400`.

### Data Display (Attendance/Rosters)
* **Rows:** Zebra striping is *disabled*. Use simple bottom borders.
* **Style:** `border-b border-slate-100 hover:bg-slate-50 transition-colors`.
* **Status Pills:** Small, capsule-shaped indicators for Status (Student/Worker).
    * *Example:* `bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full`.

---

## 4. Visual Assets Instructions

### Iconography
* **Library:** **Lucide Icons** (Clean, consistent stroke width).
* **Implementation:** Render as SVG.
* **Styling:** Stroke width `2px` (matches font weight).
* **Color:** Default to `text-slate-500` unless active (then `text-[#00BD06]`).

### Button Behaviors
* **Primary Action (Save, Check-in):**
    * `bg-[#00BD06] text-white hover:bg-[#009605] active:scale-95 transition-all shadow-lg shadow-green-500/20`.
* **Secondary Action (Cancel, Filters):**
    * `bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300`.
* **Destructive (Delete):**
    * `bg-red-50 text-red-600 hover:bg-red-100`.

### Mobile PWA Specifics
* **Navigation:** Bottom Tab Bar for mobile views (`fixed bottom-0 w-full bg-white border-t border-slate-100`).
* **Fab:** Floating Action Button for "Quick Add Attendance" on mobile dashboards.
