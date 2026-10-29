<?php

declare(strict_types=1);


$root = realpath(getenv('AETHER_TEST_PANEL') ?: '');
$zip = realpath($argv[1] ?? '');
if (!$root || !is_file($root . '/.aether-isolated-test') || !$zip || !str_ends_with($zip, '.zip')) {
    fwrite(STDERR, "Requires AETHER_TEST_PANEL with .aether-isolated-test and an install ZIP argument.\n");
    exit(2);
}
chdir($root);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = $app->make(App\Services\Helpers\PluginService::class);
$store = Aether\UI\AetherUiPlugin::store();
$before = $store->read();
$sentinel = array_replace($before, ['brand_name' => 'Lifecycle persistence check', 'radius' => 21]);
$store->write($sentinel);
try {
    $id = $service->downloadPluginFromFile(new Illuminate\Http\UploadedFile($zip, 'aether-ui.zip', 'application/zip', null, true));
    if ($id !== 'aether-ui') throw new RuntimeException('Wrong imported plugin ID');
    echo "PASS: native ZIP import\n";
    App\Models\Plugin::refreshRows();
    $plugin = App\Models\Plugin::findOrFail($id);
    $service->installPlugin($plugin);
    echo "PASS: native install and asset build\n";
    if ($store->read() !== $sentinel) throw new RuntimeException('Settings changed during native import/install');
    $service->disablePlugin($plugin);
    if ($store->read() !== $sentinel) throw new RuntimeException('Settings changed during disable');
    $service->enablePlugin($plugin);
    $service->uninstallPlugin($plugin, false);
    echo "PASS: native disable, enable and uninstall\n";
    if ($store->read() !== $sentinel) throw new RuntimeException('Settings changed during uninstall');
    $service->installPlugin($plugin);
    if ($store->read() !== $sentinel) throw new RuntimeException('Settings changed during reinstall');
    echo "PASS: native ZIP import, asset build, disable/enable, uninstall/reinstall; persistent settings retained.\n";
} finally {
    $store->write($before);
}
