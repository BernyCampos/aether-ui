<?php

declare(strict_types=1);

namespace Aether\UI;

final class Localization
{
    public static function text(string $key): string
    {
        $translator = app('translator');
        $fullKey = 'aether-ui::ui.' . $key;
        $locale = str_replace('-', '_', $translator->getLocale());
        foreach (array_unique([$locale, explode('_', $locale)[0], 'en']) as $candidate) {
            if ($translator->hasForLocale($fullKey, $candidate)) {
                return (string) $translator->get($fullKey, [], $candidate, false);
            }
        }

        return $key;
    }

    public static function welcome(array $settings, string $field): string
    {
        $value = $settings['login_' . $field];
        $original = array_map(fn (string $locale) => app('translator')->get('aether-ui::ui.login.' . $field, [], $locale, false), ['en', 'es']);

        return $value === '' || in_array($value, $original, true) ? self::text('login.' . $field) : $value;
    }
}
