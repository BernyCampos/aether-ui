# Aether UI 0.3.0 — Native Theme Switcher

Aether UI has been updated for the built-in theme picker in Pelican Panel. The plugin implements the official \`App\Contracts\Plugins\HasTheme\` contract, so its CSS, validated colors, design tokens, welcome screen, footer and native console preferences are active only while Aether is the selected theme.

## Changes

- Native per-user theme selection, with Pelican support for administrator defaults and forced themes.
- Vite stylesheet loaded by the official theme contract, without duplicate inline CSS.
- Shared Filament mode, navigation, content width, brand logo and font-provider overrides removed.
- Responsive native tables scoped to Aether only.
- Existing appearance settings retained; old global controls hidden instead of presented as working switches.
- Livewire navigation and console refresh safety checks.
- Tests, manifest and documentation updated; no new database migrations or Pelican core modifications.

## Requirements

**Pelican Panel 1.0.0-rc.3**, plugin API 1, and a supported PHP/Node environment. Do not install this release on rc.2. Aether UI 0.2.4 remains available for older panels.

## Download

Use \`aether-ui-0.3.0.zip\` to install Aether through the panel or Hub. \`aether-ui-0.3.0-source.zip\` is for source review. The release includes \`SHA256SUMS.txt\`.

## Testing

GitHub Actions checks PHP 8.4 and 8.5 syntax, Node.js runtime and selector-contract tests, and deterministic packaging. Live rc.3 staging tests of selection, default/forced themes, panel layout, existing settings, installation and updates are still recommended. The older rc.2 full integration results are not presented as rc.3 validation.
