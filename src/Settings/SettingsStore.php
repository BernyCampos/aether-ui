<?php

declare(strict_types=1);

namespace Aether\UI\Settings;

use RuntimeException;


final class SettingsStore
{
    public function __construct(private readonly string $path) {}

    public function read(): array
    {
        if (!is_file($this->path)) {
            return ThemeSettings::defaults();
        }
        $raw = file_get_contents($this->path);
        if ($raw === false || strlen($raw) > 65536) {
            throw new RuntimeException('Aether UI settings could not be read or exceed 64 KiB.');
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
        $json = json_encode(['schema_version' => 1, 'settings' => $settings], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create Aether UI settings directory.');
        }
        $lock = fopen($this->path . '.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Unable to open Aether UI settings lock.');
        }
        $temporary = null;
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to lock Aether UI settings.');
            }
            $temporary = tempnam($directory, '.aether-');
            if ($temporary === false || file_put_contents($temporary, $json) !== strlen($json)) {
                throw new RuntimeException('Unable to write Aether UI settings.');
            }
            chmod($temporary, 0600);
            
            if (!rename($temporary, $this->path)) {
                throw new RuntimeException('Unable to replace Aether UI settings.');
            }
            $temporary = null;
        } finally {
            if (is_string($temporary) && is_file($temporary)) {
                unlink($temporary);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
