# Changelog

## 0.4.0 — Aether Atmosphere (Pelican rc.4)

- Pelican Panel v1.0.0-rc.4 compatibility target via the unchanged HasTheme contract.
- Introduces Aether Atmosphere: pearl/cyan/lavender palettes, soft glass, native panel surfaces, atmospheric local artwork, animated floating particles, and more restrained controls.
- Corrects the original body selector to `html.aether-ui body` so ambient colors are actually applied.
- Keeps typography, tables, File Manager, native terminal actions and Filament styles intact.
- Adds configurable particle brightness and an animation toggle; respects system reduced-motion, reduced-transparency and reduced-data preferences.
- New installations use Atmosphere defaults; existing settings remain unchanged until the administrator applies the bundled Atmosphere preset.
- All visual assets are local. Does not modify Pelican core, add migrations or force changed permissions.
- Real panel screenshots supplied for Servers, Files, Console and Settings document dark-mode integration; additional production paths still require verification.
- Refined the translucent cards, modal surfaces and inputs with restrained blur, soft borders and subtle elevation.
- Updated README, Marketplace text, screenshot gallery and release notes for a stable 0.4.0 package. No panel-core changes.

## 0.3.0 — Native Pelican theme switcher

- Implement the official HasTheme contract for Pelican Panel 1.0.0-rc.3.
- Apply Vite CSS, color palette, design tokens, login welcome, footer, and console behavior only while Aether is selected.
- Remove global Filament layout, theme mode, sidebar, branding, and font overrides that would affect other themes.
- Apply responsive table customization only while Aether is selected.
- Retain existing settings, hide nonfunctional legacy controls, and preserve terminal behavior during Livewire navigation.
- Update automated tests and version/compatibility metadata; no new migrations or Pelican core changes.

## 0.2.4

- Packaging release for Pelican Hub distribution.

## 0.2.2

- Keep file browsing and file search in compact rows with inline actions.
- Reserve room for file sizes and modification dates on desktop.
- Keep table headers readable instead of breaking words into individual letters.
- Preserve native mobile file-column visibility and responsive records in other tables.
- Add native Filament table layout regression checks.

## 0.2.1

- Match console padding and viewport backgrounds to the configured console color.
- Remove the outer console outline while preserving terminal spacing and fitting.

## 0.2.0

- Responsive tables with labeled records, wrapping tabs and long labels.
- Consistent surfaces for tab containers, fieldsets and server statistics.
- Automatic console fitting when its container or fonts change.
- Collapsible settings sections and per-request English/Spanish translations.
- Localized default welcome text and an option to hide the welcome block.
- Presentation preset and `--merge` imports that preserve existing branding.
- Updated English documentation and release assets.

## 0.1.0

Initial theme plugin with persistent appearance, console, login and branding settings.
