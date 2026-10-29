<?php

declare(strict_types=1);

namespace Aether\UI\Settings;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class SettingsStore
{
    private const KEY = 'settings';

    public function __construct(private readonly string $legacyPath) {}

    public function read(): array
    {
        if (Schema::hasTable('aether_ui_settings')) {
            $raw = DB::table('aether_ui_settings')->where('key', self::KEY)->value('value');
            if ($raw !== null) {
                return $this->decode($raw);
            }
        }
        if (is_file($this->legacyPath)) {
            $raw = file_get_contents($this->legacyPath);
            if ($raw === false) {
                throw new RuntimeException('Unable to read legacy Aether UI settings.');
            }
            return $this->decode($raw);
        }
        return ThemeSettings::defaults();
    }

    private function decode(string $raw): array
    {
        if (strlen($raw) > 65536) {
            throw new RuntimeException('Aether UI settings exceed 64 KiB.');
        }
        $envelope = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($envelope) || ($envelope['schema_version'] ?? null) !== 1 || !is_array($envelope['settings'] ?? null)) {
            throw new RuntimeException('Unsupported Aether UI settings format.');
        }
        return ThemeSettings::normalize($envelope['settings'], false);
    }

    public function write(array $data): void
    {
        $settings = ThemeSettings::normalize($data);
        if (!Schema::hasTable('aether_ui_settings')) {
            throw new RuntimeException('Aether UI database migration has not run. Run php artisan migrate --force.');
        }
        $json = json_encode(['schema_version' => 1, 'settings' => $settings], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        DB::table('aether_ui_settings')->updateOrInsert(
            ['key' => self::KEY],
            ['value' => $json, 'updated_at' => now()]
        );
    }
}
