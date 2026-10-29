# Pelican Hub listing

**Name:** Aether UI  
**Author:** BernyC  
**Category:** Theme  
**Version:** 0.2.3  
**License:** MIT

## Description

Aether UI is a customizable light and dark theme for Pelican Panel, created by BernyC. Adjust colors, typography, spacing, corners, effects, navigation, server cards, console appearance and login branding through 82 persistent settings. It installs as a native theme plugin without modifying Pelican's core files.

This release keeps file browsing and search in compact rows with inline actions, aligns file metadata, and prevents table headers from breaking into individual letters. Dense tables adapt to narrow screens. English and Spanish interface text follows the active Pelican language, with English fallback for other locales.

**Compatibility:** Pelican Panel 1.0.0-rc.2, plugin API 1. **License:** MIT.

## Download and install

Download `aether-ui-0.2.2.zip`, import it in **Admin → Plugins**, complete installation or the update action, and enable Aether UI. Disable other appearance themes, let Pelican finish building assets, and hard-refresh the browser. Existing Aether settings are kept. Back up the panel and export settings before updating.

The `-source.zip` archive and GitHub's automatic source archives are for development. `SHA256SUMS.txt` contains checksums for both packaged archives.

## Installation troubleshooting

- **ZIP or compatibility error:** use the install ZIP and confirm Pelican 1.0.0-rc.2/API 1; do not bypass compatibility by editing the manifest.
- **Build failure or unchanged styles:** check the panel's Node/Yarn environment and build error, correct ownership or missing dependencies, retry the native installation, and refresh cached assets.
- **Settings do not save:** the PHP worker must be able to write `storage/app/aether-ui`, its lock file and temporary files. Avoid world-writable permissions.
- **HTTP 500 / JSON syntax error:** identify the failing file in the trace. Restore a malformed plugin manifest from its trusted release or restore an Aether settings backup. Leftover directories from other plugins can still be discovered.
- **Memory exhausted in panel logs:** preserve and rotate oversized logs, fix recurring errors and review the web worker's PHP-FPM memory limit against available RAM.
- **Language or mode mismatch:** check the user's Pelican locale and saved appearance preference. Custom branding text stays as entered; unsupported Aether locales use English.

See the [full installation and troubleshooting guide](https://github.com/BernyCampos/aether-ui/blob/main/docs/TROUBLESHOOTING.md) for recovery commands and reporting an issue.

## Links and screenshots

- Repository: https://github.com/BernyCampos/aether-ui
- Downloads: https://github.com/BernyCampos/aether-ui/releases/latest
- Support: https://github.com/BernyCampos/aether-ui/issues
- Use the actual isolated panel screenshots named docs/pelican-*.png. Images named preview-*.png are illustrative mockups; layout-*.png images are native component fixtures. Label them accordingly.

Review the current [Pelican Hub](https://hub.pelican.dev/) submission requirements before publishing. This project has not been submitted or approved.
