<?php

declare(strict_types=1);

namespace Aether\UI\Settings;

use InvalidArgumentException;


final class ThemeSettings
{
    public static function schema(): array
    {
        static $schema;

        return $schema ??= json_decode(file_get_contents(__DIR__ . '/../../resources/settings.schema.json'), true, 32, JSON_THROW_ON_ERROR);
    }

    public static function defaults(): array
    {
        return array_map(fn (array $field) => $field['default'], self::schema());
    }

    public static function validAsset(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        
        if (preg_match('/[\x00-\x20\x7f<>"\'()\\\\]/', $value)) {
            return false;
        }
        if (str_starts_with($value, '/') && !str_starts_with($value, '//')) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && parse_url($value, PHP_URL_SCHEME) === 'https'
            && parse_url($value, PHP_URL_USER) === null
            && parse_url($value, PHP_URL_PASS) === null;
    }

    public static function valid(string $key, mixed $value): bool
    {
        $field = self::schema()[$key] ?? null;
        if ($field === null) {
            return false;
        }

        return match ($field['type']) {
            'boolean' => is_bool($value),
            'integer' => (is_int($value) || (is_string($value) && preg_match('/^\d+$/D', $value)))
                && (int) $value >= $field['min'] && (int) $value <= $field['max'],
            'color' => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/D', $value) === 1,
            'enum' => is_string($value) && array_key_exists($value, $field['options']),
            'font' => is_string($value) && strlen($value) <= $field['max'] && preg_match('/^[a-zA-Z0-9 _-]+$/D', $value) === 1,
            'asset' => is_string($value) && strlen($value) <= $field['max'] && self::validAsset($value),
            'text' => is_string($value) && strlen($value) <= $field['max'] && !preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $value),
            default => false,
        };
    }

    public static function normalize(array $input, bool $strict = true): array
    {
        $result = self::defaults();
        foreach ($input as $key => $value) {
            $field = self::schema()[$key] ?? null;
            
            if ($value === null && $field && in_array($field['type'], ['text', 'asset'], true)) {
                $value = '';
            }
            if (!self::valid((string) $key, $value)) {
                if ($strict) {
                    throw new InvalidArgumentException('Invalid Aether UI setting: ' . $key);
                }
                continue;
            }
            $result[$key] = $field['type'] === 'integer' ? (int) $value : $value;
        }
        if ($result['lock_mode'] && $result['mode'] === 'system') {
            if ($strict) {
                throw new InvalidArgumentException('Choose light or dark before locking the theme mode.');
            }
            $result['lock_mode'] = false;
        }

        return $result;
    }
}
