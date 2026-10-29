<?php

declare(strict_types=1);


$root = realpath(getenv('AETHER_TEST_PANEL') ?: '');
if (!$root || !is_file($root . '/.aether-isolated-test')) {
    fwrite(STDERR, "Set AETHER_TEST_PANEL to a disposable Pelican rc.2 fixture with .aether-isolated-test marker.\n");
    exit(2);
}
chdir($root);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$checks = 0;
function verify(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    $checks++;
}

$plugin = new Aether\UI\AetherUiPlugin();
$defaults = Aether\UI\Settings\ThemeSettings::defaults();
verify($plugin instanceof App\Contracts\Plugins\HasPluginSettings, 'native settings contract');
verify(App\Models\Plugin::findOrFail('aether-ui')->isCompatible(), 'native compatibility check');
foreach (['admin', 'app', 'server'] as $id) {
    $panel = Filament\Facades\Filament::getPanel($id);
    verify($panel->hasPlugin('aether-ui'), 'native loader: ' . $id);
    verify($panel->getSidebarWidth() === '260px', 'sidebar options: ' . $id);
}
verify(!Filament\Facades\Filament::getPanel('app')->hasNavigation(), 'app server list navigation preserved');
$form = $plugin->getSettingsForm();
$names = [];
foreach ($form as $section) {
    foreach ($section->getDefaultChildComponents() as $field) $names[] = $field->getName();
}
verify(count($names) === 82 && count(array_unique($names)) === 82, '82 actual Filament form fields');
verify(array_diff(array_keys($defaults), $names) === [], 'schema and Filament fields agree');
foreach ([App\Filament\Server\Resources\Files\Pages\ListFiles::class, App\Filament\Server\Resources\Files\Pages\SearchFiles::class] as $pageClass) {
    $fileTable = Filament\Tables\Table::make(new $pageClass());
    verify(!$fileTable->isStackedOnMobile(), 'file tables keep row layout: ' . $pageClass);
    verify(str_contains($fileTable->getExtraAttributes()['class'] ?? '', 'aether-file-table'), 'file layout scope: ' . $pageClass);
}

$head = view('aether-ui::head', ['tokens' => Aether\UI\Theme::css($defaults), 'browserConfig' => Aether\UI\Theme::browserConfig($defaults)])->render();
verify(str_contains($head, 'window.AetherUI.mount('), 'Blade head compiled and rendered');
verify(str_contains($head, 'html.aether-ui.dark'), 'actual CSS included');
$hostile = array_replace($defaults, ['login_title' => '<script>alert(1)</script>', 'footer_text' => '<img onerror=alert(1)>']);
$login = view('aether-ui::login', ['settings' => $hostile])->render();
$footer = view('aether-ui::footer', ['settings' => $hostile])->render();
verify(!str_contains($login, '<script>') && str_contains($login, '&lt;script&gt;'), 'login text escaped by real Blade');
verify(!str_contains($footer, '<img') && str_contains($footer, '&lt;img'), 'footer escaped by real Blade');

try {
    $plugin->saveSettings($defaults);
    verify(false, 'anonymous save rejected');
} catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
    verify($e->getStatusCode() === 403, 'anonymous save rejected with 403');
}
$store = Aether\UI\AetherUiPlugin::store();
$original = $store->read();
try {
    foreach (['en' => 'Enable Aether UI', 'es' => 'Activar Aether UI', 'es-MX' => 'Activar Aether UI', 'xx' => 'Enable Aether UI'] as $locale => $label) {
        app()->setLocale($locale);
        verify(Aether\UI\Localization::text('fields.enabled') === $label, 'locale lookup: ' . $locale);
    }
    foreach (['en', 'es'] as $locale) {
        $catalog = require plugin_path('aether-ui', 'lang/' . $locale . '/ui.php');
        verify(array_diff(array_keys($defaults), array_keys($catalog['fields'])) === [], 'complete field translations: ' . $locale);
    }
    app()->setLocale('es');
    verify(Aether\UI\Localization::text('fields.enabled') !== 'Enable Aether UI', 'Spanish fields');
    verify(Aether\UI\Localization::welcome($defaults, 'title') === 'Tu universo, bajo control.', 'localized default welcome');
    app()->setLocale('en');
    verify(Aether\UI\Localization::welcome(array_replace($defaults, ['login_title' => 'Tu universo, bajo control.']), 'title') === 'Your universe, under control.', 'legacy welcome follows locale');
    verify(Aether\UI\Localization::welcome(array_replace($defaults, ['login_title' => 'Custom brand']), 'title') === 'Custom brand', 'custom welcome preserved');
    $store->write(array_replace($defaults, ['brand_name' => 'Preserved brand', 'brand_logo' => '/brand.svg']));
    $exit = Illuminate\Support\Facades\Artisan::call('aether:settings', ['operation' => 'import', 'file' => plugin_path('aether-ui', 'presets/presentation.json'), '--merge' => true, '--force' => true]);
    verify($exit === 0, 'presentation import');
    verify($store->read()['brand_name'] === 'Preserved brand' && $store->read()['brand_logo'] === '/brand.svg', 'presentation preserves branding');
    verify($store->read()['content_width'] === 'full' && $store->read()['radius'] === 16, 'presentation visual settings applied');
    foreach (['light', 'dark'] as $mode) {
        $store->write(array_replace($defaults, ['mode' => $mode, 'lock_mode' => true]));
        $panel = Filament\Panel::make()->id('test-' . $mode);
        $plugin->register($panel);
        verify($panel->hasDarkMode() === ($mode === 'dark'), 'locked mode: ' . $mode);
        verify(!$panel->hasThemeSwitcher(), 'locked mode hides native switcher');
    }
    foreach (['sidebar', 'topbar', 'mixed'] as $navigation) {
        $store->write(array_replace($defaults, ['navigation' => $navigation]));
        $panel = Filament\Panel::make()->id('admin-test');
        $plugin->register($panel);
        verify($panel->hasTopNavigation() === ($navigation === 'topbar'), 'navigation: ' . $navigation);
    }
    $store->write(array_replace($defaults, ['brand_name' => 'Example', 'brand_logo' => '/test.svg', 'brand_logo_dark' => '/dark.svg', 'brand_favicon' => '/icon.ico']));
    $panel = Filament\Panel::make()->id('branding-test');
    $plugin->register($panel);
    verify($panel->getBrandName() === 'Example', 'native brand name');
    verify($panel->getBrandLogo() === '/test.svg' && $panel->getDarkModeBrandLogo() === '/dark.svg', 'native light/dark logos');
    verify($panel->getFavicon() === '/icon.ico', 'native favicon');
    $store->write(array_replace($defaults, ['enabled' => false]));
    $panel = Filament\Panel::make()->id('disabled')->brandName('Original');
    $plugin->register($panel);
    verify($panel->getBrandName() === 'Original', 'emergency disable preserves native panel');
} finally {
    $store->write($original);
}
echo "PASS: $checks checks against Pelican " . config('app.version') . ' / Filament ' . Composer\InstalledVersions::getPrettyVersion('filament/filament') . "\n";
