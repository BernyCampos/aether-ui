# Testing

Release 0.2.3, October 29, 2026.

For 0.2.3, 44 native integration checks passed, including scoped table configuration for ListFiles and SearchFiles. In Chrome, 144 layout and menu checks passed against rendered Filament table components with their native Livewire styles and client scripts. The fixtures cover file rows, long filenames, empty tickets and populated tickets at 320, 390, 768, 1024, 1440, 1920 and 3080 px, with 16 and 20 px text. Checks measure horizontal overflow, row height, inline actions and header height, and file-menu placement and closing; no client exceptions were observed. These are isolated component fixtures, not a connected Wings file manager or an installed third-party Tickets plugin. The full installation lifecycle was not repeated for 0.2.3.

The 0.2.1 visual patch was checked in Chrome with the actual Pelican xterm bundle at 800 and 280 px. The console container and padding use the configured background, the outer outline is absent, and the visible screen remains within its container. The earlier full integration and lifecycle results below refer to 0.2.0; that full sequence was not repeated for this CSS patch. No production deployment was performed.

## Environment

The tests use an isolated copy of official Pelican Panel 1.0.0-rc.2, its Composer lockfile, Filament 5.10.2, PHP 8.4.27, SQLite and Chrome on Windows. The panel has a local test account and no production servers or customer data.

## Results

- PHP syntax checks passed for the plugin, translation catalogs, tools and tests.
- 308 PHP assertions passed for settings validation, ranges, unsafe input, persistence, atomic replacement, corruption recovery and generated design tokens.
- 12 JavaScript tests passed for terminal options, deferred initialization, reduced motion, navigation, resize fitting and observer cleanup.
- 40 native integration checks passed for the plugin loader, settings contract, 82 Filament fields, rendering, escaping, permissions, modes, branding, locale fallback and merged preset imports.
- Browser checks cover login and a native plugin table with long sample names at 320, 390, 768, 1024, 1440 and 2560 px in both appearance modes. Width checks inspect the document and component scroll widths.
- The settings form is checked at all six widths, including a saved brand followed by a full reload.
- Locale checks switch the authenticated test user between English and Spanish.
- The real xterm module and FitAddon are exercised with ANSI output, container resizing and disposal. Tabs and stat blocks are also checked using representative native component markup.
- The interactive preview is checked in both modes and on mobile, including palette controls and reduced motion.

Browser validation was completed in stages. Login, settings persistence and user-language checks passed, followed by 53 focused checks against the final table and console changes. Console checks compare the visible terminal screen with its container; xterm's invisible input helper can extend outside that screen without creating a user-facing scrollbar.

The 0.2.0 candidate ZIP passed Pelican's native import, install, asset build, disable, enable, uninstall and reinstall sequence. Settings remained intact throughout. Final archives were checked for integrity and reproducibility, and their runtime files were compared with the installed candidate. The later archive update only changes this report.

## Reproduce

Run the standalone checks from the repository:

```sh
php tools/lint.php
php tests/run.php
node --test tests/runtime.test.cjs
python tools/package.py
```

For integration tests, prepare a disposable Pelican installation with Aether enabled and create a marker file named `.aether-isolated-test` in its root. Set `AETHER_TEST_PANEL` to that directory.

```sh
php tests/integration.php
php tests/lifecycle.php /path/to/aether-ui-0.2.0.zip
```

The lifecycle test calls Pelican's own import, installation and asset-build services, then disables, enables, uninstalls and reinstalls the theme while checking preserved settings. Do not use a production installation.

For browser tests, install Playwright in your development environment, run the test panel at `http://127.0.0.1:8794`, and set:

- `AETHER_TEST_PANEL`: absolute path to the marked fixture.
- `AETHER_TEST_AUTH`: path to a local JSON file containing the test account's `email` and `password`.
- `AETHER_TEST_PHP`: optional PHP executable path.
- `AETHER_TEST_CHROME`: optional Chrome executable path.
- `AETHER_TEST_URL`: optional test panel URL.

Run `node tests/browser.integration.cjs`. It temporarily adds sample plugin manifests and changes the test user's language and theme settings, restoring them in a finally block. Never include test credentials in release assets.

For the table layout regression suite, build the disposable panel assets, install Playwright and set `AETHER_TEST_PANEL`. `AETHER_TEST_PHP` and `AETHER_TEST_CHROME` are optional executable overrides. No running web server or credentials are required. Set `AETHER_TABLE_OUTPUT` to retain fixtures and screenshots in a chosen directory; otherwise a temporary directory is created.

```sh
node tests/tables.browser.cjs
```

The `layout-*.png` images show these isolated native component fixtures using the presentation palette.

## Not verified

Live Wings connections, power commands and reconnections; every administrative resource or third-party plugin; production Linux workers and web servers; NFS or multi-host writes; MFA, OAuth and recovery flows; Safari, Firefox, physical devices and a full accessibility audit. PHP 8.5 is configured in CI but was not run locally.

The example tab and stat-block checks do not replace end-to-end tests of every node or server page. Browser width checks cover the listed configurations, not every possible combination of custom settings.

GitHub Actions results are available in the repository's Actions tab. No Pelican Hub submission or approval is implied by this report.

