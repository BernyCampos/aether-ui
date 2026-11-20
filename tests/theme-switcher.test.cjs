'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const read = p => fs.readFileSync(path.join(__dirname, '..', p), 'utf8');

test('Aether Atmosphere 0.4.0 targets the Pelican rc.4 theme picker', () => {
    const manifest = JSON.parse(read('plugin.json'));
    assert.equal(manifest.id, 'aether-ui');
    assert.equal(manifest.category, 'theme');
    assert.equal(manifest.api_version, 1);
    assert.equal(manifest.version, '0.4.0');
    assert.doesNotMatch(manifest.description, /preview|beta|test build/i);
    assert.equal(manifest.panel_version, '1.0.0-rc.4');
    assert.equal(JSON.parse(read('package.json')).version, manifest.version);
    assert.ok(!('meta' in manifest));
});
test('HasTheme interface replaces all global Filament panel overrides', () => {
    const php = read('src/AetherUiPlugin.php');
    assert.match(php, /implements Plugin, HasTheme, HasPluginSettings/);
    for (const method of ['getThemeViteEntry', 'getThemeColors', 'getThemeFont', 'getThemeRenderHooks']) {
        assert.match(php, new RegExp('function ' + method + '\\('));
    }
    assert.match(php, /plugins\/aether-ui\/resources\/css\/aether\.css/);
    const register = php.split('public function register(Panel $panel): void')[1].split('public function boot(Panel $panel)')[0];
    assert.doesNotMatch(register, /->(?:colors|defaultThemeMode|darkMode|themeSwitcher|font|maxContentWidth|sidebarWidth|sidebarCollapsibleOnDesktop|topNavigation|topbar|brandName|brandLogo|darkModeBrandLogo|favicon|brandLogoHeight|renderHook)\(/);
    for (const hook of ['HEAD_END', 'AUTH_LOGIN_FORM_BEFORE', 'FOOTER']) {
        assert.match(php, new RegExp('PanelsRenderHook::' + hook));
    }
    assert.match(php, /array_replace\(\$this->getSettingsFormData\(\), \$data\)/);
});
test('scoped CSS tokens, safe bootstrap and Livewire theme changes', () => {
    const head=read('resources/views/head.blade.php');
    assert.match(head, /meta name="aether-ui-active"/);
    assert.match(head, /aether-ui-tokens/);
    assert.doesNotMatch(head, /file_get_contents\(.+resources\/css\/aether\.css/);
    assert.match(head, /JSON_HEX_TAG/);
    const js=read('resources/js/aether.js');
    assert.match(js, /meta\[name="aether-ui-active"\]/);
    assert.match(js, /activeTheme\(\)/);
    assert.match(read('src/Theme.php'), /'enabled' => true/);
});
test('responsive tables are modified only when Aether is active', () => {
    const provider=read('src/Providers/AetherUiServiceProvider.php');
    assert.match(provider, /ThemeService::class/);
    assert.match(provider, /isActive\('aether-ui'\)/);
    const form=read('src/Settings/SettingsForm.php');
    for (const key of ['mode','navigation','brand_logo','sidebar_width','content_width']) assert.match(form,new RegExp("'" + key + "'"));
});


test('Atmosphere particle effects are configurable and honor user motion preferences', () => {
    const schema = JSON.parse(read('resources/settings.schema.json'));
    assert.equal(schema.particles.default, true);
    assert.equal(schema.particles.type, 'boolean');
    assert.equal(schema.particle_intensity.min, 0);
    assert.equal(schema.particle_intensity.max, 100);
    const php = read('src/Theme.php');
    assert.match(php, /'particle-opacity'/);
    assert.match(php, /'particle-play'/);
    const css = read('resources/css/aether.css');
    assert.match(css, /@keyframes aether-particles-float/);
    assert.match(css, /@keyframes aether-particles-depth/);
    assert.match(css, /prefers-reduced-motion: reduce/);
    assert.match(css, /pointer-events: none/);
    assert.match(css, /forced-colors: active/);
    assert.match(css, /html\.aether-ui body::before/);
});


test('0.4.0 release documentation includes all real panel screenshots', () => {
    const gallery = read('docs/SCREENSHOTS.md');
    const readme = read('README.md');
    for (const name of ['aether-servers.webp','aether-files.webp','aether-console.webp','aether-settings.webp']) {
        assert.ok(fs.existsSync(path.join(__dirname, '..', 'docs/screenshots', name)), name);
        assert.match(gallery, new RegExp('screenshots/' + name.replaceAll('.', '\\.')));
    }
    assert.match(readme, /docs\/SCREENSHOTS\.md/);
    assert.match(gallery, /real running panel/);
    assert.match(read('docs/RELEASE.md'), /0\.4\.0/);
});


test('real screenshot documentation exists and stays outside the install-only asset bundle', () => {
    const fs = require('node:fs');
    const path = require('node:path');
    const root = path.join(__dirname, '..');
    for (const page of ['servers', 'files', 'console', 'settings']) {
        assert.ok(fs.existsSync(path.join(root, 'docs', 'screenshots', `aether-${page}.webp`)), `${page} screenshot`);
    }
    const readme = read('README.md');
    assert.match(readme, /docs\/screenshots\/aether-servers\.webp/);
    assert.doesNotMatch(readme, /docs\/preview\.html|Your next great world|82 controls/);
});
