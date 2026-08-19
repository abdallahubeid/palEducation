---
version: 2.3
name: palEducation-design-system
status: ADOPTED — implemented and verified in code (~52 components across 19 built screens, exercised on /styleguide)
description: "Design system for a Palestinian Tawjihi (high-school exit exam) study platform. Indigo-purple #525fe1 carries every action (buttons, links); amber #f0a500 is the sole secondary color, reserved for specific moments; both sit on one background, #f5f8f7, never stark white. Headings run in navy #0b104a. The system is bidirectional by design (Arabic RTL primary, English LTR secondary), with letter-spacing hard-zeroed and line-heights raised to suit connected Arabic glyphs. Every color/text pairing in this file is measured and confirmed to clear WCAG AA."

# ═══════════════════════════════════════════════════
# Source of truth: resources/css/app.css → @theme
# Any change here must be mirrored there, and vice versa.
# ═══════════════════════════════════════════════════

colors:
  # ── Action & brand ──
  accent: "#525fe1"
  accent-deep: "#3f4ab8"
  accent-soft: "#ecedff"
  accent-on-dark: "#bcc2f8"

  # ── Secondary color ──
  amber: "#f0a500"
  amber-deep: "#9c6205"

  # ── Surfaces ──
  ground: "#f5f8f7"
  canvas: "#ffffff"
  canvas-dark: "#0b104a"
  surface: "#f7f7f7"
  surface-soft: "#fafafa"
  surface-code: "#1c1c1e"
  deep-from: "#242e83"
  deep-to: "#3945a3"
  sky-from: "#c7cbf5"
  sky-to: "#eef0fb"

  # ── Borders ──
  hairline: "#e5e5e5"
  hairline-soft: "#ededed"
  hairline-strong: "#d4d4d4"
  hairline-dark: "#1f1f1f"

  # ── Text ──
  primary: "#0b104a"
  on-primary: "#ffffff"
  ink: "#0b104a"
  charcoal: "#1a2d62"
  slate: "#3d4a6b"
  steel: "#4a5355"
  stone: "#666e7c"
  muted: "#a3aab5"
  on-dark: "#ffffff"
  on-dark-muted: "#b3b3b3"

  # ── Feedback / status ──
  tag: "#3772cf"
  warn: "#c37d0d"
  warn-deep: "#8a5a09"
  error: "#d45656"
  error-deep: "#b23b3b"

typography:
  hero:    { fontFamily: IBM Plex Sans Arabic, fontSize: 56px, fontWeight: 700, lineHeight: 1.35, letterSpacing: 0 }
  display: { fontFamily: IBM Plex Sans Arabic, fontSize: 44px, fontWeight: 700, lineHeight: 1.35, letterSpacing: 0 }
  h1:      { fontFamily: IBM Plex Sans Arabic, fontSize: 36px, fontWeight: 700, lineHeight: 1.35, letterSpacing: 0 }
  h2:      { fontFamily: IBM Plex Sans Arabic, fontSize: 28px, fontWeight: 600, lineHeight: 1.40, letterSpacing: 0 }
  h3:      { fontFamily: IBM Plex Sans Arabic, fontSize: 24px, fontWeight: 600, lineHeight: 1.45, letterSpacing: 0 }
  h4:      { fontFamily: IBM Plex Sans Arabic, fontSize: 20px, fontWeight: 600, lineHeight: 1.50, letterSpacing: 0 }
  h5:      { fontFamily: IBM Plex Sans Arabic, fontSize: 18px, fontWeight: 600, lineHeight: 1.55, letterSpacing: 0 }
  lead:    { fontFamily: IBM Plex Sans Arabic, fontSize: 18px, fontWeight: 400, lineHeight: 1.80, letterSpacing: 0 }
  body:    { fontFamily: IBM Plex Sans Arabic, fontSize: 16px, fontWeight: 400, lineHeight: 1.75, letterSpacing: 0 }
  ui:      { fontFamily: IBM Plex Sans Arabic, fontSize: 15px, fontWeight: 400, lineHeight: 1.70, letterSpacing: 0 }
  caption: { fontFamily: IBM Plex Sans Arabic, fontSize: 14px, fontWeight: 400, lineHeight: 1.60, letterSpacing: 0 }
  micro:   { fontFamily: IBM Plex Sans Arabic, fontSize: 13px, fontWeight: 600, lineHeight: 1.50, letterSpacing: 0 }
  numeric: { fontFamily: Inter, fontVariantNumeric: tabular-nums }
  code:    { fontFamily: Geist Mono, direction: ltr }

rounded:
  xs: 4px
  sm: 6px
  md: 8px
  lg: 12px
  xl: 16px
  xxl: 24px
  full: 9999px

spacing:
  scale: "4px base — 4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 48 · 64 · 96"
  container-max: 1280px
  reading-max: 68ch
  section: "py-20 on mobile · py-24 from lg"

elevation:
  subtle: "0 1px 2px 0 rgb(0 0 0 / 0.04)"
  card: "0 4px 12px 0 rgb(0 0 0 / 0.08)"
  mockup: "0 24px 48px -8px rgb(0 0 0 / 0.12)"
  accent-glow: "0 8px 24px 0 rgb(82 95 225 / 0.10)"
  focus: "0 0 0 3px rgb(82 95 225 / 0.32)"

motion:
  fast: "120ms cubic-bezier(0.4, 0, 0.2, 1)"
  base: "200ms cubic-bezier(0.4, 0, 0.2, 1)"
  reveal: "600ms cubic-bezier(0.16, 1, 0.3, 1)"
  slide: "1000ms — matches smartSpeed in the reference template"
---

## Overview

palEducation is a **study tool** a Palestinian teenager opens in the evening and spends two hours inside before the most important exam of their life. Every visual decision is measured against one question:

> **Does this reduce cognitive load on a tired, anxious student, or add to it?**

### The five pillars

1. **Purple is the action color** — `{colors.accent}` on every primary button, link, and active element. One primary button per screen.
2. **Amber is the secondary color** — `{colors.amber}` appears in a limited set of places: stat tiles, the free-trial banner, branch/track identity.
3. **No stark-white background** — `{colors.ground}` `#f5f8f7` for the whole site; cards sit white on top of it. Pure white tires the eye over a long study session.
4. **Flat by default** — hierarchy comes from hairline borders and spacing. One deep shadow, reserved for the mockup.
5. **Arabic breathes** — 1.75 line-height, zero letter-spacing, 16px minimum for anything read as text.

### System sources

| Source | What was taken from it |
|---|---|
| **edunova** | The full color palette: `#525fe1` · `#0b104a` · `#1a2d62` · `#4a5355` |
| **Charitize** | Hero paper texture · double-rule badge · stats checkerboard grid · teacher card · testimonial carousel |
| **Mintlify** | Radius/spacing scale and the "marketing surface vs. functional surface" logic |

---

## Color Palette

### Primary — Action Color

`{colors.accent}` `#525fe1` at **235°**. Appears on: primary button, links, active nav item, progress bar fill, focus ring.

- `{colors.accent-deep}` `#3f4ab8` — text on light surfaces
- `{colors.accent-on-dark}` `#bcc2f8` — text on dark surfaces
- `{colors.accent-soft}` `#ecedff` — faint background tint

### Secondary — Accent Color

`{colors.amber}` `#f0a500` at **39°** — roughly 196° from purple, close to true complementary. Blue-and-gold is a stable pairing, and in an education context it reads as **achievement**, not danger.

> **Why not coral or mint:** coral `#f26b65` sits at 3° — a red that collides with an otherwise cool palette. Mint `#00d4a4` sits at 165° — too close to the semantic "success" green. Light purple `#8b5cf6` and magenta `#d946a0` fail text contrast outright (4.2:1, both).

**Scarcity rule:** exactly two chromatic colors, system-wide. A third dilutes amber's signal. Semantic colors (`tag` · `warn` · `error`) are used **only for their meaning**, never as decoration.

### Neutrals — Surfaces

| Token | Value | Role |
|---|---|---|
| `ground` | `#f5f8f7` | Whole-site background — never stark white |
| `canvas` | `#ffffff` | Card / surface background |
| `canvas-dark` | `#0b104a` | Dark-band background |
| `surface` / `surface-soft` | `#f7f7f7` / `#fafafa` | Secondary surfaces |
| `surface-code` | `#1c1c1e` | Reserved for code blocks / dark inline surfaces — defined, not yet used (no code-display component exists) |
| `deep-from → deep-to` | `#242e83 → #3945a3` | The one gradient allowed in the system, for dark hero/CTA bands |
| `sky-from → sky-to` | `#c7cbf5 → #eef0fb` | Light, airy gradient for hero backgrounds |

### Neutrals — Borders

| Token | Value | Role |
|---|---|---|
| `hairline` | `#e5e5e5` | Card borders |
| `hairline-soft` | `#ededed` | Row dividers |
| `hairline-strong` | `#d4d4d4` | Hover / active border state |
| `hairline-dark` | `#1f1f1f` | Reserved for borders on dark/code surfaces — defined, not yet used |

### Neutrals — Text

| Token | Value | Role |
|---|---|---|
| `ink` | `#0b104a` | Headings |
| `charcoal` | `#1a2d62` | Default body text |
| `slate` | `#3d4a6b` | Secondary text |
| `steel` | `#4a5355` | Paragraph text |
| `stone` | `#666e7c` | Tertiary text (darkened to clear 4.5:1) |
| `muted` | `#a3aab5` | Placeholder / disabled text |
| `on-dark` / `on-dark-muted` | `#ffffff` / `#b3b3b3` | Text on dark surfaces |

### Feedback / Status

| Token | Value | Meaning |
|---|---|---|
| `tag` | `#3772cf` | Branch / track badges |
| `warn` | `#c37d0d` | Subscription expiring soon — **surface only, fails as text (3.14:1)** |
| `warn-deep` | `#8a5a09` | `warn` text on light surfaces (5.54:1) |
| `error` | `#d45656` | Subscription expired · wrong answer — **surface only, fails as text (3.73:1)** |
| `error-deep` | `#b23b3b` | `error` text on light surfaces (5.49:1) |

**Domain semantics:**

| State | Color |
|---|---|
| Active subscription · correct answer · completed lecture | `{colors.accent}` |
| Subscription expiring soon | `{colors.warn}` |
| Subscription expired · wrong answer | `{colors.error}` |
| Branch / track label | `{colors.tag}` |

> 🔴 **Red clarifies, it doesn't scold.** `{colors.error}` `#d45656` is deliberately muted, with an 8%-opacity background tint. No giant ✗ icon, no shake animation, no sound.

---

## 🔴 Contrast Rule — Non-Negotiable

**No color/text pairing ships unmeasured.** Every pair below is measured and confirmed:

| Surface | Permitted text | Ratio |
|---|---|---|
| `{colors.ground}` | `ink` · `charcoal` · `slate` · `steel` · `stone` | 4.8 – 16.6:1 |
| `{colors.ground}` | `accent` · `accent-deep` · `amber-deep` · `error-deep` · `warn-deep` | 4.7 – 6.8:1 |
| `{colors.accent}` `#525fe1` | **white only** | 5.14:1 |
| `{colors.amber}` `#f0a500` | **navy (`ink`) only** — white gives 2.07:1 | 8.50:1 |
| `{colors.amber-deep}` `#9c6205` | **white only** | 5.04:1 |
| `{colors.deep-from}` / `deep-to` | white · `accent-on-dark` | 6.8 – 11.7:1 |
| `{colors.canvas-dark}` `#0b104a` | white · `accent-on-dark` | 10.3 – 17.7:1 |

### Three traps this project actually hit

**1. One shade of a color is never enough — each color needs two.**
`amber` `#f0a500` is an excellent surface color (8.5:1 against navy) but only **2.07:1 as text** — unreadable. Hence `amber-deep` for text. Same logic applies to `accent` / `accent-deep`. The same trap resurfaced with the two semantic status colors: `warn` measured **3.14:1** and `error` **3.73:1** as text — both fail AA — while working fine as 12–14%-opacity tinted backgrounds. `warn-deep` (5.54:1) and `error-deep` (5.49:1) were added as the text-only shades; `badge.blade.php` was updated to use them. **Known gap:** several other files still reference bare `text-error` / `text-warn` for text and haven't been swept yet (see Known Gaps).

**2. Lightening a dark surface silently breaks whatever sits on top of it.**
When the dark band was lightened from `#0b104a` to `#242e83`, `accent` on top of it dropped from 3.44:1 to **2.28:1**. `accent-on-dark` `#bcc2f8` (6.8:1) was added to fix it. **Lightening any surface requires re-measuring everything on top of it.**

**3. Swapping a color can flip the direction of contrast.**
Coral `#f26b65` was dark enough to just barely carry white text. Amber `#f0a500` is light, which flipped the rule — four spots were still putting white on a light background. **Whenever a color is swapped: re-check every spot that carries text over it.**

---

## Typography Scale

| Style | Family | Size | Weight | Line-height | Letter-spacing |
|---|---|---|---|---|---|
| Hero | IBM Plex Sans Arabic | 56px | 700 | 1.35 | 0 |
| Display | IBM Plex Sans Arabic | 44px | 700 | 1.35 | 0 |
| H1 | IBM Plex Sans Arabic | 36px | 700 | 1.35 | 0 |
| H2 | IBM Plex Sans Arabic | 28px | 600 | 1.40 | 0 |
| H3 | IBM Plex Sans Arabic | 24px | 600 | 1.45 | 0 |
| H4 | IBM Plex Sans Arabic | 20px | 600 | 1.50 | 0 |
| H5 | IBM Plex Sans Arabic | 18px | 600 | 1.55 | 0 |
| Lead | IBM Plex Sans Arabic | 18px | 400 | 1.80 | 0 |
| Body | IBM Plex Sans Arabic | 16px | 400 | 1.75 | 0 |
| UI text | IBM Plex Sans Arabic | 15px | 400 | 1.70 | 0 |
| Caption | IBM Plex Sans Arabic | 14px | 400 | 1.60 | 0 |
| Micro | IBM Plex Sans Arabic | 13px | 600 | 1.50 | 0 |
| Numeric | Inter | — | — | — | `tabular-nums` |
| Code | Geist Mono | — | — | — | `dir="ltr"` |

**Font assignment:** Arabic interface text runs in **IBM Plex Sans Arabic** (400/500/600/700). Latin text and numbers run in **Inter**. Math and code run in **Geist Mono** with `dir="ltr"`. All fonts load **locally** via `laravel-vite-plugin`'s font support (Bunny) — no CDN, no external request.

### Binding rules

1. **`letter-spacing: 0` on every Arabic character, no exceptions.** `tracking-*` utilities are removed at the Tailwind theme root (`--tracking-*: initial` in `app.css`), so they can't be generated at all — the trap is structurally impossible, not just documented.
2. **≥16px for anything a student reads** · **15px floor for UI chrome** · **14px reserved for metadata only**.
3. **No `text-transform: uppercase`** on Arabic text.
4. **Numbers are always Western 0–9**, set in Inter with `tabular-nums` in every table and score.
5. **Reading measure ≤68 characters** (`.measure` utility).

---

## Spacing & Layout

**Base unit:** 4px. **Scale:** 4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 48 · 64 · 96

| Setting | Value |
|---|---|
| Container max-width | 1280px |
| Reading measure max | 68ch (`.measure`) |
| Section rhythm | `py-20` on mobile → `py-24` from `lg` |

### Radius scale

| Token | Value | Assignment convention |
|---|---|---|
| `xs` | 4px | Tiny chips |
| `sm` | 6px | Small badge chips |
| `md` | 8px | Inputs and small controls |
| `lg` | 12px | — |
| `xl` | 16px | Cards, modal panels |
| `xxl` | 24px | — |
| `full` | 9999px | Buttons — always fully pill-shaped |

---

## Direction — RTL by Default

**There is no `left` or `right` anywhere in this project.** Logical properties only: `ms/me` · `ps/pe` · `text-start/end` · `border-s/e` · `start-*/end-*` · `inset-inline`.

**Always stays LTR:** video player · math equations · code · numbers and dates · email addresses and links.

**Mixed text:** any Latin fragment inside an Arabic sentence is wrapped in `<bdi>`.

**Registered RTL trap:** styles ported from Latin-first templates hardcode `left`. When porting any style, convert it to `inset-inline` or it renders on the wrong side.

Full rules: [.claude/rules/rtl-bilingual.md](.claude/rules/rtl-bilingual.md)

---

## Component Design Tokens

### Buttons — always pill-shaped (`{rounded.full}`)

| Variant | Style | Hover |
|---|---|---|
| `primary` | `accent` bg + white text | `amber-deep` bg |
| `accent` | `amber` bg + `ink` text | `accent` bg + white text |
| `on-dark` | white bg + `accent` text | `accent` bg + white text |
| `secondary` | transparent + `hairline` border | `surface` bg |
| `ghost` | transparent, `{rounded.md}` (not pill) | `surface` bg + `ink` text |
| `danger` | `error` bg + white text | dimmed brightness |

**Sizes:** `sm` 44px → 36px from `lg` · `md` 48px → 44px from `lg` · `lg` 52px (fixed, no shrink).
Tablets are touch input too, so the size reduction happens at the `lg` breakpoint (desktop pointer input), not at `sm`.

**Rule:** one primary button per screen, no exceptions.

### Badges

| Variant | Style |
|---|---|
| `neutral` | `surface` bg, `steel` text |
| `branch` | `tag` @12% bg, `tag` text |
| `accent` | `accent` @14% bg, `accent-deep` text |
| `warn` | `warn` @14% bg, `warn` text |
| `error` | `error` @12% bg, `error` text |
| `duration` | `surface` bg, `steel` text, Inter font, `tabular-nums` |

**Shape:** `pill` (`rounded-full`, default) or `chip` (`rounded-sm`, alternate). Both: micro text (13px/600), horizontal padding 10px, vertical padding 2px.

### Cards — unified `.tile`

`canvas` background · `{rounded.xl}` · no border · shadow `0 0 30px rgb(0 0 0 / 0.05)`, deepening to `0 0 30px rgb(0 0 0 / 0.12)` with a 4px lift on hover. The hover state is built into `.tile` itself — there is no separate `.tile-hover` modifier class.

> **Note:** `app.css` also defines a bordered `.card` / `.card-hover` pair (`canvas` bg, `hairline` border, `{rounded.xl}`, the `elevation.card` shadow on hover) that matches the documented card elevation token exactly — but it isn't applied anywhere in the current markup. Treat it as reserved for a future bordered-card variant, not the active pattern.

**Featured state** (pricing card): 2px `accent` ring + `accent-glow` shadow, no border change.

### Inputs — built (`components/ui/input.blade.php`)

Built and in use across the Profile and Subject-search screens. Token spec, as implemented:

- **Height:** 48px, 44px from `lg` — matches `button.md`, so an input paired with a `md` button aligns without extra CSS
- **Radius:** `{rounded.md}` (8px) — one step down from card radius, so an input reads as sitting *inside* a card
- **Background:** `canvas` · **Border:** 1px `hairline`
- **Hover:** border → `hairline-strong`
- **Focus:** border → `accent` + the standard focus ring (`0 0 0 3px rgb(82 95 225 / 0.32)`) — the same ring token used everywhere else, no input-only variant
- **Error state:** border → `error`, background → `error` @8% (the same muted-red convention used on quiz results — red clarifies, it doesn't scold)
- **Disabled:** 50% opacity, `surface` background, no hover state
- **Placeholder text:** `muted` · **Label:** `ui` size (15px), `steel`, positioned above the field
- **Horizontal padding:** 16px

> **Implementation note:** the outer wrapper applies `$attributes->except('id')->merge([...])` so a caller's `class` (e.g. `max-w-40`) survives — an early version used `except(['id','class'])` and silently dropped it. Fixed to match the pattern already established in `ui/tabs.blade.php`.

### Modals / Dialogs — built (`components/ui/modal.blade.php`, `components/ui/confirm-dialog.blade.php`)

`ConfirmDialog` wraps `Modal` for the delete/destructive-action case. As implemented:

- **Overlay:** `ink` at 45% opacity (`rgb(11 16 74 / 0.45)`) — tinted with the brand ink rather than pure black, consistent with "no pure black in this system"
- **Panel:** `canvas` background, `{rounded.xl}`, `{elevation.mockup}` — the one deep-elevation shadow in the system, otherwise reserved for the hero mockup; a modal is the only other element that sits high enough above the page to earn it
- **Width:** 480px for confirm-style dialogs (`ConfirmDialog`), up to 640px for content-bearing modals · full-width bottom sheet below the `sm` breakpoint
- **Padding:** 32px on desktop, 24px on mobile
- **Close control:** `ghost`-variant icon button, positioned **top-end** (never top-right — see Direction)
- **Entry motion:** `{motion.base}` (200ms), opacity + slight scale — a dialog interrupts the student, so it should feel immediate rather than use the slower `{motion.reveal}` timing reserved for scroll content
- **Behavior:** traps focus while open, closes on <kbd>Escape</kbd> and on overlay click, returns focus to the triggering element on close

### Built components inventory

Grown well past the Wave-1 homepage set — now ~45 Blade component files across four layers:

**Shells & layout** (`layouts/`, `layout/`): `layouts.public` (PublicShell) · `layouts.student` (StudentShell, holds the `[data-shell]` focus/collapse state target) · `layouts.focus` (standalone quiz/quiz-result fallback pages) · **`layouts.centered`** (CenteredShell — one shell serving all 7 auth and system-boundary screens; width via `narrow` 440px / `wide` 560px / `full` 1024px) · **`layouts.styleguide`** (internal dev surface) · `layout.sidebar` (collapsible, icon-only when collapsed, mobile drawer) · `layout.topbar` (notification-bell flyout, subscription badge, search)

**UI primitives** (`ui/`): `button` · `badge` · `input` · **`select`** · **`checkbox`** · **`radio`** · **`password-input`** · **`form-errors`** · **`selectable-card`** · `modal` · `confirm-dialog` · `alert` · `avatar` · `breadcrumb` · `tabs` · `pagination` · `progress-bar` · `empty-state` · `toggle` · `score-ring` · `accordion-item` (native `<details name>`, zero JS) · `media-slot` · `section-head` · `rule-label`

> **The six new form primitives were designed on `/styleguide` before being used in any screen** — deliberately, because stateful components are exactly what has broken repeatedly in this project. All of them are built from plain Tailwind utilities with **no custom class on the element carrying the state**, so `peer-checked:` and `has-checked:` stay inside one cascade layer. Two mechanics worth knowing:
> - `peer-checked:` matches **siblings only** (`~`). To style a descendant of the sibling, reach through it: `peer-checked:[&>svg]:opacity-100`, not `peer-checked:opacity-100` on the descendant itself.
> - In Tailwind 4, `scale-*` writes the standalone `scale` property, **not** `transform`. Verify with `getComputedStyle(el).scale`.

**Domain components** (`domain/`): `branch-card` · `plan-card` · `teacher-card` · `subject-card` · `subject-hero` · `stat-card` · `continue-card` · `lecture-list-item` · `module-accordion` · `topic-accordion` · `topic-item` (the text/video/quiz 3-part row) · `video-player` · `quiz-option` · `quiz-result-card` · `answer-review-row` · `file-row` · `notification-item` · `plan-summary-card` · `streak-tracker` · `study-bar-chart`

**Site chrome** (`site/`): `header` · `footer` · `icon` (grown past 12 inline SVGs as new screens needed new glyphs)

Every list-bearing component above ships an `EmptyState` state where the design calls for one; every destructive action routes through `ConfirmDialog`. None of this is wired to real data yet — every page still supplies its own `@php` mock array (see Known Gaps).

### Patterns carried over from Charitize

- **`rule-label`** — two rules crossing the text, top rule +40px / bottom rule +60px overhang, built with `inset-inline` so it mirrors correctly in RTL
- **Stats grid** — 4 tiles, 48px padding, 48px number, 48px icon, two alternating colors
- **Teacher card** — square photo + vertical icon rail
- **"About" photo pair** — two images at 33%×90px in opposite corners
- **Testimonial carousel** — fixed title panel + slides with photo, quote, and stacked arrows

### Slider

Slides are stacked on `grid-area: 1/1` so the layout never jumps between them · crossfade transition, not a horizontal slide (this sidesteps RTL direction complexity entirely) · `overflow: hidden` on `[data-slider]` is mandatory — rotating slides produce horizontal scroll without it.

---

## Motion

| Token | Value | Use |
|---|---|---|
| `fast` | 120ms `cubic-bezier(0.4, 0, 0.2, 1)` | Micro-interactions |
| `base` | 200ms `cubic-bezier(0.4, 0, 0.2, 1)` | Hover/focus state changes, modal entry |
| `reveal` | 600ms `cubic-bezier(0.16, 1, 0.3, 1)` | Scroll-triggered reveal, staggered |
| `slide` | 1000ms | Matches `smartSpeed` in the reference template (slider fold effect) |

> 🔴 **Fail-safe rule:** scroll-reveal hiding is gated on a `.js-reveal` class that JS adds itself. If JS fails to load, the class is never added and content renders normally. **Never hide with CSS what CSS alone can't guarantee to show again.**

All motion is disabled under `prefers-reduced-motion` — content appears, it does not disappear.

---

## Elevation

| Token | Value | Use |
|---|---|---|
| `subtle` | `0 1px 2px 0 rgb(0 0 0 / 0.04)` | Minimal lift |
| `card` | `0 4px 12px 0 rgb(0 0 0 / 0.08)` | Defined; currently only wired to the unused `.card-hover` pair (see Cards) |
| `mockup` | `0 24px 48px -8px rgb(0 0 0 / 0.12)` | Hero product shot · modal panels |
| `accent-glow` | `0 8px 24px 0 rgb(82 95 225 / 0.10)` | Featured pricing card only |
| `focus` | `0 0 0 3px rgb(82 95 225 / 0.32)` | Every interactive element's focus-visible ring |

---

## Do's and Don'ts

### ✅ Do
- Use `accent` for every action · `amber` for the secondary color only
- **Measure every new color/text pairing before adopting it**
- Use the `-deep` text shade, not the surface shade, when writing text
- Use logical properties on every line
- Zero `letter-spacing` on all Arabic text · line-height ≥1.75
- Wrap every Latin fragment inside an Arabic sentence in `<bdi>`
- `EmptyState` for every list · `ConfirmDialog` for every delete
- `overflow-hidden` on any container holding moving slides

### ❌ Don't
- Don't write a hex value in a component — everything comes from `@theme`
- Don't put white text on `amber` (2.07:1)
- Don't use `accent` as text on a dark surface — use `accent-on-dark`
- Don't introduce a third chromatic color
- No `ml-` / `mr-` / `text-left` / `text-right`
- No `letter-spacing` on Arabic text · no readable text under 16px
- No Eastern Arabic-Indic digits (٠-٩)
- No more than one primary button per screen
- Never mirror the video player

---

## Responsive Breakpoints

| Breakpoint | Width | Change |
|---|---|---|
| Mobile | <640px | Single column · nav drawer · touch targets ≥44px · `hero` → 36px |
| Tablet | 768–1023px | 2-up grid · nav drawer · touch targets ≥44px |
| Desktop | 1024–1279px | 3–4 grid · horizontal nav · pointer-sized controls |
| Wide | ≥1280px | Container caps at 1280px |

**Verified:** zero horizontal overflow at 360 · 480 · 753 · 1009 · 1425px — at rest and during both slider animations.

---

## Accessibility & Contrast Standards

- **Minimum 4.5:1 contrast** for all text — every pairing measured in the Contrast Rule table above
- **Visible focus ring on every interactive element** — never `outline: none`
- **Touch target ≥44×44px** up to the `lg` breakpoint — `checkbox`, `radio` and `toggle` use `py-2.5 lg:py-1.5` to hit 46px on touch and relax on pointer (measured; they were 38–42px before the fix)
- `aria-hidden` + `tabindex="-1"` on inactive slider slides
- Accordion built on `<details name>` — works with JavaScript fully disabled
- `alt` text on every image · descriptive `aria-label` on every icon-only control
- Respects `prefers-reduced-motion`

---

## Performance

- Built assets, never the Vite dev server — **4.7× difference** in load time (dev path recompiles Tailwind's full CSS on every request, measured at 2.02s for one file; production build: ~1.3s total)
- Fonts self-hosted · images use `loading="lazy"` except above-the-fold
- Texture and grain are inline SVG — zero network request

> ⚠️ `npm run dev` creates `public/hot` and silently re-enables the slow dev path. For browsing, run `npm run build` and stop the dev server.

---

## Known Gaps

| # | Gap | Status |
|---|---|---|
| 1 | Logo | ⏳ Not delivered yet |
| 2 | Real teacher photos | ⏳ Current set is from the source template |
| 3 | Subscription scope — determines `plan-card`'s final shape | ⏳ Pending decision M-1 |
| 4 | Quiz behavior | ⏳ Pending decision M-2 — the built quiz/result screens implement docs/01 §7's *recommended default* (2 attempts, highest score kept, no lecture lock) as demo/localStorage logic. This is a mock standing in for a decision, not the decision itself. |
| 5 | Payment mechanism | ⏳ Pending decision M-4 — no checkout/payment screens built yet |
| 6 | Video hosting provider | ⏳ Pending decision M-5 — `video-player` is a demo shell (play/pause + a "simulate lecture end" trigger), not a real player integration |
| 7 | Dark mode | ⏳ Not built yet |
| 8 | Input component | ✅ Built — `ui/input.blade.php` |
| 9 | Modal / Dialog component | ✅ Built — `ui/modal.blade.php`, `ui/confirm-dialog.blade.php` |
| 10 | `text-error` / `text-warn` bare-color usage | ✅ **Closed 2026-08-20** — swept across all views (`alert`, `branch-card`, `stat-card`, `teacher-card`, `media-slot`, `home`). Every occurrence is now `-deep`; verified by grep: 11 × `text-error-deep`, 11 × `text-warn-deep`, zero bare. |
| 11 | No backend | ⏳ Every screen built this far is Blade + static PHP `@php` mock arrays. No Eloquent models, migrations, seeders, auth, or Livewire — see `docs/01-project-understanding.md` §9 for the full current technical status. |
| 12 | `/styleguide` is publicly routable | ⏳ Carries `noindex` only. **Must be gated or excluded before any production deploy** — `noindex` is not access control. |
| 13 | Teacher & Admin shells | ⏳ Not built. `PublicShell`, `StudentShell`, `FocusShell` and `CenteredShell` exist; the remaining two arrive with Waves 7–8. |
