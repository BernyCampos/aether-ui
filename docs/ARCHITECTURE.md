# Architecture

Aether UI 0.3.0 integrates through Pelican's official \`App\Contracts\Plugins\HasTheme\` interface introduced in Panel 1.0.0-rc.3. It retains plugin ID \`aether-ui\`, API version 1 and category \`theme\`.

The plugin's \`register()\` does not modify global Filament layout, color mode, branding or assets. \`getThemeViteEntry()\` exposes \`plugins/aether-ui/resources/css/aether.css\` for Pelican's Vite build; \`getThemeColors()\` exposes validated palette values. \`getThemeFont()\` returns null because Aether fonts are applied through its scoped CSS tokens.

\`getThemeRenderHooks()\` provides the design tokens, browser terminal adapter, welcome display and optional footer. Pelican's ThemeService gates them so they render only while Aether is the active user/default/forced theme. The script uses a head marker for Livewire navigation to stop applying the Aether document class when the user leaves Aether. The native console, Wings connections and authentication remain unchanged.

\`AetherUiServiceProvider\` adds responsive native Filament table behavior only when \`ThemeService::isActive('aether-ui')\` is true. It keeps ListFiles and SearchFiles dense without hiding their original operations.

\`ThemeSettings\` validates settings through the existing schema. \`SettingsStore\` preserves current storage behavior and legacy values, including the existing database table when present and JSON fallback. On save, non-displayed legacy values are merged with existing settings; removed global controls cannot silently reset previously saved information. No new migrations, direct core patches or custom installation scripts are introduced.

\`Localization\` uses Pelican's active locale with English fallback. The Xterm adapter modifies only presentation options and maintains native fitting and cleanup.
