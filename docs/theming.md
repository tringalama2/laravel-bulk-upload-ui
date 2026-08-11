# Theming

All three frontends (Livewire, React, Vue) share the same visual style, driven by a set of CSS custom properties scoped under `.bulk-upload-wizard` — the root element of the wizard on every frontend. Override any of them and it applies everywhere the wizard renders, with no view publishing or file editing required.

The default declaration wraps in `:where(.bulk-upload-wizard)`, which carries zero specificity — a plain `.bulk-upload-wizard { ... }` rule in your own CSS always wins over ours, in any order, without `!important`.

## Getting your CSS onto the wizard's pages

The wizard's pages are deliberately isolated from the rest of your app — Livewire's is a fully self-contained document, and React/Vue render through their own Inertia root view rather than yours — so your app's normal `app.css` never reaches them, however it's loaded. Point `laravel-bulk-upload-ui.extra_stylesheet` (or the `BULK_UPLOAD_UI_EXTRA_STYLESHEET` env var) at the public URL of a small CSS file, and it's linked on every wizard page, after the wizard's own styles:

```
BULK_UPLOAD_UI_EXTRA_STYLESHEET=/css/bulk-upload-ui-overrides.css
```

That file can be as simple as a static file in `public/css/`, or something your own Vite build emits — either way, nothing needs to be published or edited on the package's side. Everything below assumes your overrides live in that file.

## Variables

| Variable | Controls | Light default | Dark default |
|---|---|---|---|
| `--bulk-upload-ui-font-family` | Font stack for the whole wizard | `system-ui, sans-serif` | *(same)* |
| `--bulk-upload-ui-color-text` | Body text | `#1f2937` | `#e5e7eb` |
| `--bulk-upload-ui-color-text-muted` | Hint text (accepted formats, etc.) | `#6b7280` | `#9ca3af` |
| `--bulk-upload-ui-color-border` | Table and cell borders | `#e5e7eb` | `#374151` |
| `--bulk-upload-ui-color-danger` | Validation error text | `#b91c1c` | `#f87171` |
| `--bulk-upload-ui-color-danger-bg` | Invalid row background | `#fef2f2` | `#450a0a` |
| `--bulk-upload-ui-color-primary` | Button background | `#111827` | `#e5e7eb` |
| `--bulk-upload-ui-color-primary-text` | Button text | `#ffffff` | `#111827` |
| `--bulk-upload-ui-radius` | Button corner radius | `.375rem` | *(same)* |

## Overriding with plain CSS

Works regardless of your CSS stack — Tailwind, Bootstrap, or nothing at all. Add this anywhere in your own stylesheet, loaded after the wizard's:

```css
.bulk-upload-wizard {
    --bulk-upload-ui-color-primary: #7c3aed;
    --bulk-upload-ui-radius: .5rem;
}
```

## Overriding with Tailwind v4

If your app already defines brand colors in a Tailwind `@theme` block, point our variables at yours instead of hardcoding a second copy:

```css
.bulk-upload-wizard {
    --bulk-upload-ui-color-primary: var(--color-primary);
    --bulk-upload-ui-color-primary-text: var(--color-primary-foreground);
    --bulk-upload-ui-radius: var(--radius-md);
}
```

From then on the wizard tracks your Tailwind theme automatically. There's no `content`-scanning concern here — you're only ever setting plain CSS custom properties, not applying Tailwind utility classes to the wizard's markup (which you don't control, since you link to the wizard's route rather than writing its HTML yourself).

## Dark mode

The dark values above apply automatically via `@media (prefers-color-scheme: dark)` — no configuration needed. If you want the wizard to ignore the visitor's OS/browser preference and stay in one mode regardless, declare a single plain (non-media-queried) `.bulk-upload-wizard { ... }` rule with all the variables set to your chosen values. Because both our light and dark defaults are wrapped in `:where()`, a normal selector like that always outranks them — load order doesn't matter, and it beats our dark block even while the visitor's OS is in dark mode.
