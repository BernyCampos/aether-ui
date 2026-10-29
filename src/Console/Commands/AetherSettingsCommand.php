<?php

declare(strict_types=1);

namespace Aether\UI\Console\Commands;

use Aether\UI\AetherUiPlugin;
use Aether\UI\Settings\ThemeSettings;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

final class AetherSettingsCommand extends Command
{
    protected $signature = 'aether:settings {operation : export, import or reset} {file? : JSON file for import/export} {--merge : Preserve settings omitted from an import} {--force : Confirm updating current settings}';

    protected $description = 'Export, import or reset Aether UI settings (schema version 1)';

    public function handle(): int
    {
        try {
            $store = AetherUiPlugin::store();
            $operation = $this->argument('operation');
            if ($operation === 'export') {
                $json = json_encode(['schema_version' => 1, 'settings' => $store->read()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
                if ($this->argument('file')) {
                    throw new RuntimeException('Direct file exports are not supported by Pelican Hub. Omit the file argument and redirect console output to a backup file outside the plugin.');
                }
                $this->line($json);

                return self::SUCCESS;
            }
            if (!in_array($operation, ['import', 'reset'], true)) {
                throw new RuntimeException('Use export, import or reset.');
            }
            if (!$this->option('force')) {
                throw new RuntimeException('Import/reset updates settings. Back up with export, then pass --force.');
            }
            $data = ThemeSettings::defaults();
            if ($operation === 'import') {
                $file = $this->argument('file');
                if (!$file || !is_file($file) || filesize($file) > 65536) {
                    throw new RuntimeException('Provide a JSON file smaller than 64 KiB.');
                }
                $envelope = json_decode(file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
                if (($envelope['schema_version'] ?? null) !== 1 || !is_array($envelope['settings'] ?? null)) {
                    throw new RuntimeException('Expected schema_version 1 and settings object.');
                }
                $data = $this->option('merge') ? array_replace($store->read(), $envelope['settings']) : $envelope['settings'];
            }
            $store->write($data);
            $this->info('Saved. Reload the panel.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
