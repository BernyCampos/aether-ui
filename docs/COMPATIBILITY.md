# Compatibility

| Component | Verified baseline |
| --- | --- |
| Pelican Panel | 1.0.0-rc.2 |
| Plugin API | 1 |
| Filament | 5.10.2 from the Pelican lockfile |
| PHP | 8.4.27 on Windows |
| Browser | Chrome / Chromium |
| Terminal | xterm 6 and native FitAddon |

The manifest intentionally pins the tested panel release. Recheck plugin loading, Filament markup and the exposed `window.Xterm` API before widening this constraint.

Use one active appearance theme. Third-party plugins may supply their own fixed-width layouts or styles and need separate testing. Very dense tables may use more vertical space to keep every field and action available.

English and Spanish catalogs are bundled. Regional variants use the matching base language; missing translations fall back to English. Add `lang/<locale>/ui.php` using the English catalog structure for more languages.

The theme preserves Pelican permissions and authentication. Live Wings connections, real server power operations, MFA flows, Safari, Firefox and production Linux workers require additional validation. The GitHub workflow includes PHP 8.5, but that job has not been run locally.
