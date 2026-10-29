<?php

declare(strict_types=1);

use Aether\UI\Settings\SettingsStore;
use Aether\UI\Settings\ThemeSettings;
use Aether\UI\Theme;

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'Aether\\UI\\')) {
        require __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 10)) . '.php';
    }
});

$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $checks++;
}
function rejects(callable $callable, string $label): void
{
    try {
        $callable();
    } catch (Throwable) {
        check(true, $label);

        return;
    }
    check(false, $label);
}

$defaults = ThemeSettings::defaults();
check(count($defaults) === 82, 'all 82 settings have defaults');
check(ThemeSettings::normalize($defaults) === $defaults, 'default round trip');
foreach (ThemeSettings::schema() as $key => $field) {
    check(ThemeSettings::valid($key, $field['default']), 'valid default ' . $key);
    if ($field['type'] === 'integer') {
        check(ThemeSettings::valid($key, $field['min']), 'inclusive min ' . $key);
        check(ThemeSettings::valid($key, $field['max']), 'inclusive max ' . $key);
        check(!ThemeSettings::valid($key, $field['min'] - 1), 'min bound ' . $key);
        check(!ThemeSettings::valid($key, $field['max'] + 1), 'max bound ' . $key);
    }
    if ($field['type'] === 'enum') {
        foreach (array_keys($field['options']) as $option) {
            check(ThemeSettings::valid($key, $option), 'enum option ' . $key);
        }
    }
    check(!ThemeSettings::valid($key, []), 'reject arrays ' . $key);
}
foreach (['javascript:alert(1)', '//evil.example/logo', 'data:image/svg+xml,x', '/x"</style>', '/x\\foo', "https://x.test/\n", 'https://u:p@x.test/image', 'http://x.test/a'] as $url) {
    check(!ThemeSettings::validAsset($url), 'unsafe URL rejected');
}
foreach (['', '/storage/logo.svg', 'https://cdn.example.com/a.woff2?v=2'] as $url) {
    check(ThemeSettings::validAsset($url), 'safe asset accepted');
}
rejects(fn () => ThemeSettings::normalize(['primary' => 'red;}</style><script>']), 'CSS injection rejected');
rejects(fn () => ThemeSettings::normalize(['custom_font' => 'x";}']), 'font CSS injection rejected');
rejects(fn () => ThemeSettings::normalize(['enabled' => 'false']), 'string boolean rejected');
rejects(fn () => ThemeSettings::normalize(['lock_mode' => true]), 'system cannot be forced');
rejects(fn () => ThemeSettings::normalize(['unknown' => true]), 'unknown option rejected');
check(ThemeSettings::normalize(['brand_name' => null])['brand_name'] === '', 'cleared nullable field');
check(ThemeSettings::normalize(['spacing' => '32'])['spacing'] === 32, 'integer form values');
check(ThemeSettings::normalize(['primary' => 'invalid'], false)['primary'] === $defaults['primary'], 'bad persisted field falls back');
check(ThemeSettings::normalize(['lock_mode' => true], false)['lock_mode'] === false, 'corrupt locked system recovered');

$directory = sys_get_temp_dir() . '/aether-test-' . bin2hex(random_bytes(6));
$store = new SettingsStore($directory . '/settings.json');
try {
    check($store->read() === $defaults, 'fresh install defaults');
    $changed = ThemeSettings::normalize(['primary' => '#123abc', 'radius' => 7, 'brand_name' => '<script>text only</script>']);
    $store->write($changed);
    check((new SettingsStore($directory . '/settings.json'))->read() === $changed, 'persisted across instances');
    for ($i = 0; $i < 10; $i++) {
        $store->write(array_replace($changed, ['radius' => $i]));
        check($store->read()['radius'] === $i, 'atomic replacement');
    }
    $before = file_get_contents($directory . '/settings.json');
    rejects(fn () => $store->write(['radius' => 99]), 'invalid write rejected');
    check(file_get_contents($directory . '/settings.json') === $before, 'invalid save preserves last good state');
    check(count(glob($directory . '/.aether-*')) === 0, 'temporary files cleaned');
    file_put_contents($directory . '/settings.json', '{broken');
    rejects(fn () => $store->read(), 'corruption explicitly reported');
    file_put_contents($directory . '/settings.json', '{"schema_version":2,"settings":{}}');
    rejects(fn () => $store->read(), 'future schema explicitly rejected');
    $store->write($defaults);
    check($store->read() === $defaults, 'reset recovers corrupt file');
} finally {
    foreach (glob($directory . '/*') ?: [] as $file) unlink($file);
    if (is_dir($directory)) rmdir($directory);
}

$css = Theme::css($defaults);
check(str_contains($css, 'html.aether-ui.dark{'), 'separate light/dark tokens');
check(str_contains($css, '--aether-radius:18px'), 'radius mapped to CSS');
check(!str_contains(Theme::css(['primary' => '</style>']), '</style>'), 'render boundary rejects injection');
check(str_contains(Theme::css(['font' => 'custom', 'font_url' => '/fonts/a.woff2']), '@font-face'), 'custom local font');
check(!str_contains(Theme::css(['font' => 'custom', 'font_url' => 'javascript:evil']), '@font-face'), 'invalid persisted font URL safe');
$config = Theme::browserConfig($defaults);
check(count($config['terminal']['theme']) === 20, 'ANSI and terminal colors mapped');
check($config['terminal']['theme']['selectionBackground'] === $defaults['console_selection'], 'modern xterm selection key');
check($config['terminal']['lineHeight'] === 1.3, 'console line height converted');
check(Theme::browserConfig(['motion' => false, 'console_cursor_blink' => true])['terminal']['cursorBlink'] === false, 'motion toggle applies to cursor');
check(!array_key_exists('brand_name', $config), 'only terminal presentation config exposed to JS');
check(version_compare('1.0.0-rc.2', json_decode(file_get_contents(__DIR__ . '/../plugin.json'), true)['panel_version'], '='), 'strict compatibility matches rc.2');
echo "PASS: $checks PHP checks\n";
