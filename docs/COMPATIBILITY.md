# Compatibility

| Component | Release target |
| --- | --- |
| Pelican Panel | 1.0.0-rc.3 |
| Theme interface | App\Contracts\Plugins\HasTheme |
| Plugin API | 1 |
| PHP | 8.4 / 8.5 (CI syntax checks) |
| Browser | Modern Chromium |
| Terminal | Pelican native xterm and FitAddon |

**Aether UI 0.3.0 requires Pelican 1.0.0-rc.3.** Older rc.2 panels do not expose HasTheme; the previous release 0.2.4 remains available for those installations.

With the native picker, other switchable theme plugins can remain enabled. Aether CSS, hooks, colors and Filament table customizations apply only while selected. Global mode, lock, navigation layout, panel size and brand logo controls cannot be safely scoped to individual users, so they have been hidden and disabled without deleting the corresponding legacy saved values.

The repository's earlier native integration results targeted rc.2. Live rc.3 switching, default/forced themes, admin/app/server layout regressions, upgrade and removal, third-party plugins, Safari/Firefox and Wings require additional staging validation before production use.
