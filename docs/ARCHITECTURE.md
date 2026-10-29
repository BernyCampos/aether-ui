# Architecture

`plugin.json` declares a theme plugin for API 1. Pelican discovers the main plugin class, service provider, translation catalogs and console command through its plugin loader.

`ThemeSettings` validates the shared schema. `SettingsForm` builds native Filament controls. `SettingsStore` writes a versioned JSON envelope using a lock, temporary file and atomic rename. Invalid saves leave the previous file intact. Reads recover invalid individual fields with defaults; unreadable or corrupt envelopes are reported and the panel renders defaults. Concurrent saves use last-write-wins behavior.

`AetherUiPlugin` registers native colors, fonts, navigation and branding, plus scoped styles and render hooks. `AetherUiServiceProvider` enables Filament's native stacked-table markup while the theme is active. CSS uses the table container width to choose a readable record layout without removing columns. Native ListFiles and SearchFiles pages receive a scoped class and keep their row layout, inline actions and existing mobile column visibility. Header text wraps only where a component explicitly requests it.

`Localization` resolves the active request locale, its base language and English fallback. Custom content remains unchanged. Stock welcome text from the first release migrates to automatic translation.

The browser adapter wraps Pelican's exposed xterm Terminal and FitAddon classes. Public terminal options control appearance; ResizeObserver and font events request fitting. Disposal removes observers. The adapter does not replace server connections, command handlers or authentication.

No database migrations, core view replacements, telemetry or external script services are included. Images and font URLs configured by an administrator may cause browser requests to those hosts.
