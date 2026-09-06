---
name: MSystem
description: Bootstrap 3 (Flatly theme) point-of-sale UI for NT CellStore, built for cashier speed under counter-light conditions.
colors:
  slate-navbar: "#2c3e50"
  slate-navbar-active: "#1a242f"
  topbar-navy: "#182735"
  teal-success: "#18bc9c"
  sky-info: "#3498db"
  amber-warning: "#f39c12"
  red-danger: "#e74c3c"
  white: "#ffffff"
  border-gray: "#dce4ec"
  text-body: "#2c3e50"
typography:
  brand:
    fontFamily: "Lato, Helvetica Neue, Helvetica, Arial, sans-serif"
    fontSize: "clamp(1rem, 1.5vw, 1.25rem)"
    fontWeight: 400
    lineHeight: 1.2
  body:
    fontFamily: "Lato, Helvetica Neue, Helvetica, Arial, sans-serif"
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.4286
  pageTitle:
    fontFamily: "Lato, Helvetica Neue, Helvetica, Arial, sans-serif"
    fontSize: "22px"
    fontWeight: 400
  mono:
    fontFamily: "Menlo, Monaco, Consolas, Courier New, monospace"
rounded:
  none: "0px"
  sm: "4px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
components:
  navbar-module-item:
    backgroundColor: "{colors.slate-navbar}"
    textColor: "{colors.white}"
    rounded: "{rounded.none}"
  navbar-module-item-active:
    backgroundColor: "{colors.white}"
    textColor: "{colors.text-body}"
    rounded: "{rounded.none}"
  topbar:
    backgroundColor: "{colors.topbar-navy}"
    textColor: "{colors.white}"
    padding: "0.2em"
---

# Design System: MSystem

## 1. Overview

**Creative North Star: "The Counter Console"**

MSystem is the screen behind the register, not a product a customer ever opens. Every choice serves the cashier mid-transaction, with a line at the counter and store lighting washing out anything low-contrast. It runs on Bootstrap 3's Flatly theme (dark slate `#2c3e50` navbar, flat teal/amber/red state colors, 4px corners) with a handful of local overrides, and this is explicitly a transitional state: the project is on a path to Bootstrap 5, so this spec documents what exists today for consistency, not a visual direction to invest further in. Don't add new decoration on top of a system that's being replaced.

This is not a SaaS dashboard performing competence with cards, gradients, and hero metrics, and it is not a legacy spreadsheet either: it inherits Flatly's flat, high-contrast, no-frills language and should stay there. Two stacked bars carry global chrome: a slim dark **topbar** (clock, company name, session controls) above a **module navbar** (icon-grid of the modules the logged-in employee is allowed to see).

**Key Characteristics:**
- Flat everywhere: no shadows, no gradients, 4px or 0px corners only.
- Two-tier header: 0.2em-padding topbar (session/clock) stacked on the Flatly navbar (module nav).
- Module nav items are icon-over-label, not icon-beside-label: an SVG (24px) with the module name below it.
- High-contrast state colors (teal/blue/amber/red) reserved for buttons and badges, never for large surfaces.

## 2. Colors

Inherited Bootswatch Flatly palette plus two local additions (topbar navy, border gray). Flat and saturated at the extremes by Bootstrap 3 convention, not tinted-neutral OKLCH; this is legacy Bootstrap 3 CSS, not a from-scratch token system.

### Primary
- **Slate Navbar** (`#2c3e50`): background of the module navbar and of `.btn-primary`. The dominant "chrome" color across every screen.

### Secondary
- **Topbar Navy** (`#182735`): the thin session bar above the navbar (clock, company name, change-password/logout). Darker than the navbar itself, marking it as a separate, lower-priority strip.

### Tertiary
- **Teal Success** (`#18bc9c`), **Sky Info** (`#3498db`), **Amber Warning** (`#f39c12`), **Red Danger** (`#e74c3c`): Bootstrap contextual colors. Used on buttons, badges, and alerts only, never as a surface fill.

### Neutral
- **White** (`#ffffff`): page background, card/panel background, and the text color of nav links and the topbar.
- **Border Gray** (`#dce4ec`): local addition, used for dropdown/tagsinput borders (`ospos.css`).
- **Body Text** (`#2c3e50`): same value as Primary, reused as the default text color; also the color the active nav link switches to when it sits on a white pill.

### Named Rules
**The No-New-Chrome-Color Rule.** This palette is inherited, not designed. Don't introduce a new hex value for a new UI element; reuse one of the above. If none fits, that's a signal the element belongs in the Bootstrap 5 migration, not patched into Bootstrap 3.

## 3. Typography

**Brand/Body Font:** Lato, with Helvetica Neue, Helvetica, Arial, sans-serif fallback
**Mono Font:** Menlo, Monaco, Consolas, Courier New, monospace (barcode/reference values only)

**Character:** A plain, workhorse grotesque at small sizes. Nothing here is chosen for expression, it's chosen for legibility at 13px body text under store lighting.

### Hierarchy
- **Brand** (400, navbar-brand text, line-height 1.2): "MSystem" wordmark in the navbar header, white on slate, hidden below the `sm` breakpoint.
- **Page Title** (400, 22px): `#page_title`, one per screen, sets the current module/action.
- **Body** (400, 13px, line-height 1.4286): `.wrapper` default; the working size for tables, forms, and nav labels.
- **Label/Mono** (400, inherits mono stack): barcode and reference-number contexts only.

### Named Rules
**The Legibility-Over-Density Rule.** Given the counter-light, high-pressure use case in PRODUCT.md, never shrink body text below 13px to fit more into a table. Add a horizontal scroll or drop a column first.

## 4. Elevation

Flat by default, no shadow vocabulary anywhere in `ospos.css` or the Flatly theme itself. Depth is conveyed by flat color blocking (white content well between two dark bars) and by the mobile nav's box-shadow, which is the one deliberate exception.

### Shadow Vocabulary
- **Mobile nav reveal** (`box-shadow: inset 0 1px 0 rgba(0,0,0,.06), 0 6px 12px rgba(0,0,0,.1)`): applied only to `.navbar-collapse.collapse.in` / `.collapsing` on small screens, to separate the opened module list from page content beneath it.

### Named Rules
**The Flat-Chrome Rule.** The topbar and navbar are solid color fills, never gradients or shadowed panels. If it needs to look "lifted," that's a Bootstrap 5 conversation, not a Bootstrap 3 patch.

## 5. Components

### Navigation
- **Structure:** two stacked full-width bars. Topbar (`.topbar`, navy `#182735`, 12px text, 0.2em padding) holds a three-column row: live clock (left), company name (center, bold), change-password + logout (right). Below it, the module navbar (`.navbar.navbar-default`, Flatly slate `#2c3e50`) holds the brand wordmark plus a `navbar-toggle` hamburger on the left, and the icon-grid of allowed modules on the right.
- **Module item, desktop:** each `<li>` is icon-over-label: a 24px SVG, a `<br>`, then the module's translated name, all inside one `<a class="menu-icon">`. White text/icon on slate background at rest.
- **Module item, active:** local override in `ospos.css` inverts Flatly's own default (`#1a242f` darker navy) to a **white pill with `#2c3e50` text** instead. This is the one meaningful custom decision, not a Bootstrap default. Keep it: a filled-white "you are here" pill reads faster at a glance than a subtly darker background, which matters when a cashier is scanning the nav mid-transaction.
- **Mobile (below 768px):** the icon-grid becomes a proper single-column touch list (`display:flex`, icon + label side-by-side, 22px icon, 12-16px padding, divider lines between items, active item gets a light `#f4f6f8` background instead of the desktop white pill). This responsive rewrite already lives in `header.php`'s inline `<style>` block and is correct: don't regress it back to the cramped icon-grid on small screens.
- **Topbar on mobile:** clock and company name are hidden entirely (`display:none`); only session controls (change password / logout) remain, centered, with ellipsis truncation on long names. Deliberate simplification, not an oversight.

## 6. Do's and Don'ts

### Do:
- **Do** keep the navbar and topbar flat solid fills (`#2c3e50` / `#182735`). No gradients, no shadows on these bars.
- **Do** keep the active-module white pill (`background:#fff; color:#2c3e50`) rather than reverting to Flatly's default darker-navy active state; it's the higher-contrast, faster-to-scan choice this project already made on purpose.
- **Do** treat any navbar/topbar change as provisional. This is Bootstrap 3 on the way to Bootstrap 5: prefer the smallest patch that fixes the real problem over a redesign of chrome that's slated for replacement.
- **Do** keep body text at 13px minimum and page titles at 22px; both were sized for counter-distance legibility, not screen-share aesthetics.
- **Do** preserve the existing mobile nav rewrite (flex touch list, 22px icons, divider lines) instead of falling back to the desktop icon-grid at small widths.

### Don't:
- **Don't** make this look like a legacy spreadsheet: no dense unstyled tables, no tiny unlabeled icon-only controls, no gray-on-gray low-contrast text. That is the project's stated anti-reference.
- **Don't** make this look like a generic SaaS dashboard: no card-grid homepage, no hero-metric tiles, no gradient accents bolted onto the flat Flatly chrome.
- **Don't** introduce a new accent color for a single feature. Reuse the existing state colors (teal/blue/amber/red) or defer the decision to the Bootstrap 5 migration.
- **Don't** add shadows, blur, or glassmorphism to the topbar/navbar. Flat is the entire visual language here.
- **Don't** use `border-left`/`border-right` colored stripes as a design pattern anywhere in this system; nothing in the current UI does this and it shouldn't start with the navbar.
