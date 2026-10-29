# Installation and troubleshooting

Aether UI 0.2.2 targets Pelican Panel 1.0.0-rc.2 and plugin API 1. Use the matching panel release and one active appearance theme. This guide applies to the plugin; it does not require editing Pelican's core files.

## Install or update

1. Back up the panel. If Aether UI is already installed, export its settings to a new file in an existing, writable backup directory.
2. Download `aether-ui-0.2.2.zip` from [GitHub Releases](https://github.com/BernyCampos/aether-ui/releases/latest). GitHub's automatic source archives and the `-source.zip` archive are intended for development.
3. Open **Admin → Plugins**, import the install ZIP, and complete installation or the update action. Let Pelican finish its asset build.
4. Enable Aether UI, disable other appearance themes, and reload the browser with Ctrl + F5.
5. Open Aether UI's **Settings** to customize the appearance. Existing settings are stored outside the plugin directory and survive an update.

Run Aether commands from the Pelican installation directory using the same operating-system account that owns the panel's writable files. Replace the example backup path with an existing directory on your server.

```sh
php artisan aether:settings export /safe/path/aether-before-update.json
```

## Common issues

| Symptom | What to check and how to resolve it |
| --- | --- |
| ZIP rejected, missing manifest, or theme not listed | Use the install ZIP from Releases. Its structure is `aether-ui/plugin.json` with `src`, `resources` and `lang` alongside it. Check the download and checksum, PHP's ZIP extension, and the panel/web-server upload limits. Do not rename the plugin ID or add extra nested folders. |
| Unsupported panel or plugin API version | This release targets Pelican 1.0.0-rc.2 and API 1. Use a compatible theme release or follow Pelican's supported upgrade procedure after taking a backup. Changing the manifest's version constraint does not make an incompatible panel compatible. |
| Dependency installation or asset build fails | Check that the panel process can run the Node and Yarn versions required by your Pelican release. Pelican rc.2 runs `yarn install` and `yarn build` from the panel root. Check the reported build error, available disk space, dependency-network access and file ownership, then retry the native plugin update/install action. |
| Theme is enabled but looks unchanged | Confirm that Aether is enabled in Plugins and in its own settings, finish the asset build, disable other appearance themes and hard-refresh the browser. If a reverse proxy or CDN caches static assets, invalidate the affected assets after rebuilding. |
| Settings cannot be saved, locked or replaced | The panel process needs write access to `storage/app/aether-ui` and its files, including the lock file. Check the actual PHP worker account, ownership, disk space and read-only mounts. Directory write permission is required because saves use a temporary file and atomic rename. Do not use `chmod 777`. |
| HTTP 500 with `JsonException` or `Syntax error` | Inspect the exception trace to identify the JSON file. Any malformed `plugins/*/plugin.json` can disrupt plugin discovery. Back up the affected file and restore it from that plugin's trusted release. For Aether settings, restore a known-good export. Do not edit files under `vendor` to suppress the error. |
| A supposedly removed plugin still causes errors | Check whether its directory and `plugin.json` still exist under `plugins/`. Uninstall it through Pelican when possible. If a broken plugin prevents the panel from loading, preserve a backup and move that specific plugin directory outside `plugins/` during maintenance, then retry. A renamed folder inside `plugins/` may still be discovered. |
| HTTP 500 with `Allowed memory size ... exhausted` | Check the operation and log sizes. Large log files can exhaust memory in a log viewer. Preserve and rotate old logs, investigate repeated errors, and set a PHP-FPM memory limit appropriate to the server's available RAM. Reload the correct FPM service after validating its configuration. Changing CLI PHP alone does not change the web worker's limit. |
| Aether settings JSON is damaged | Preserve a copy of `storage/app/aether-ui/settings.json`. Restore a known-good export with the import command below. To deliberately return to defaults, use the reset command only after preserving the damaged file; reset discards custom settings. Do not rely on export to back up a file that cannot be parsed. |
| Appearance mode or language differs between users | Pelican's saved user appearance preference takes precedence over the default mode. Aether follows the active Pelican locale: English and Spanish are bundled, regional variants use their base language, and other locales fall back to English. Custom branding text is not translated. Leave welcome text blank to use its translated default. |
| Table headers break into letters or file actions stack | Update to Aether UI 0.2.2, let the native asset build finish and hard-refresh. Remove conflicting custom overrides or other themes. File rows use inline actions; the native mobile layout hides size and modification-date columns. Other dense tables reflow into labeled records. |
| Console has an unexpected dark frame | Use 0.2.2, complete the asset build and refresh. The console padding and viewport use the configured console background. Check for older theme CSS or a second active appearance theme. |
| Custom fonts or images do not appear | Use a reachable HTTPS URL or site-relative path. Check for blocked requests, mixed content and font CORS restrictions. Use the system font or Pelican-provided Inter to isolate an external font issue. |

Restore an exported settings file:

```sh
php artisan aether:settings import /safe/path/aether-before-update.json --force
```

Reset after preserving the existing settings file:

```sh
php artisan aether:settings reset --force
```

To identify invalid plugin or Aether JSON without printing its contents, run from the panel root:

```sh
php <<'PHP'
<?php
$files = glob('plugins/*/plugin.json') ?: [];
$files[] = 'storage/app/aether-ui/settings.json';
foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }
    try {
        json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        echo "OK: $file\n";
    } catch (Throwable $error) {
        echo "ERROR: $file: {$error->getMessage()}\n";
    }
}
PHP
```

## Roll back or get help

Disable Aether UI in **Admin → Plugins** to return to the panel's default appearance. Settings remain in `storage/app/aether-ui/settings.json`. To revert an update, use your backed-up plugin release and settings through the native installation workflow.

If the panel remains broken with Aether disabled, inspect the underlying panel error and other plugins before changing theme settings. Do not enable public debug output on a production panel.

[Open an issue](https://github.com/BernyCampos/aether-ui/issues) with your Pelican and Aether versions, PHP and Node versions, reproduction steps, browser/viewport information for visual issues, and a short relevant error excerpt. Remove passwords, tokens, session values, personal details and private server addresses. Do not upload `.env`, full logs or production settings files.

Use the official [Pelican installation guide](https://pelican.dev/docs/panel/getting-started/) and [panel update guide](https://pelican.dev/docs/panel/update/) for panel dependencies and upgrades. See [testing](TESTING.md) for the exact checks performed and the remaining limitations.
