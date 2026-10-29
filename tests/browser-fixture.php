<?php

declare(strict_types=1);

$root = realpath(getenv('AETHER_TEST_PANEL') ?: '');
$authFile = getenv('AETHER_TEST_AUTH');
if (!$root || !is_file($root . '/.aether-isolated-test') || !$authFile || !is_file($authFile)) {
    throw new RuntimeException('A marked disposable panel and test credentials are required.');
}
chdir($root);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$auth = json_decode(file_get_contents($authFile), true, 32, JSON_THROW_ON_ERROR);
$user = App\Models\User::where('email', $auth['email'])->firstOrFail();
$store = Aether\UI\AetherUiPlugin::store();
$snapshot = $root . '/.aether-browser-state.json';
$operation = $argv[1] ?? '';
if ($operation === 'prepare') {
    if (is_file($snapshot)) throw new RuntimeException('Restore the previous browser fixture before preparing another.');
    file_put_contents($snapshot, json_encode(['settings' => $store->read(), 'language' => $user->language], JSON_THROW_ON_ERROR));
    $preset = json_decode(file_get_contents(__DIR__ . '/../presets/presentation.json'), true, 32, JSON_THROW_ON_ERROR);
    $store->write(array_replace(Aether\UI\Settings\ThemeSettings::defaults(), $preset['settings']));
    $user->update(['language' => 'en']);
} elseif ($operation === 'restore') {
    $data = json_decode(file_get_contents($snapshot), true, 32, JSON_THROW_ON_ERROR);
    $store->write($data['settings']);
    $user->update(['language' => $data['language']]);
    unlink($snapshot);
} elseif ($operation === 'locale' && in_array($argv[2] ?? '', ['en', 'es'], true)) {
    $user->update(['language' => $argv[2]]);
} else {
    throw new RuntimeException('Use prepare, restore or locale en|es.');
}
echo "Fixture updated.\n";
