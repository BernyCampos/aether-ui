# Compatibility

| Component | Release target |
| --- | --- |
| Pelican Panel | 1.0.0-rc.4 |
| Theme interface | App\Contracts\Plugins\HasTheme |
| Plugin API | 1 |
| PHP | 8.4 / 8.5 (CI syntax checks) |
| Browser | Modern Chromium |
| Terminal | Pelican native xterm and FitAddon |

**Aether UI 0.4.0 requires Pelican 1.0.0-rc.4.** Older rc.2 panels do not expose HasTheme; the previous release 0.2.4 remains available for those installations.

With the native picker, other switchable theme plugins can remain enabled. Aether CSS, hooks, colors and Filament table customizations apply only while selected. Global mode, lock, navigation layout, panel size and brand logo controls cannot be safely scoped to individual users, so they have been hidden and disabled without deleting the corresponding legacy saved values.

The repository's earlier full integration suite targeted rc.2; `HasTheme` was introduced for rc.3. The screenshot evidence now covers visible dark-mode pages on rc.4, but full forced/default theme behavior, upgrade and removal, third-party plugins, Safari/Firefox, production Linux workers and Wings actions still require separate validation.

**Evidence:** Real user-provided Pelican rc.4 dark-mode screenshots document server list, file manager, console and plugin settings. Automated PHP/Node/packaging checks are required for release. Other modes, browsers and lifecycle paths have not been comprehensively verified; preserve 0.3.0 for an rc.3 rollback.
