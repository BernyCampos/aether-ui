<?php

declare(strict_types=1);

namespace Aether\UI\Settings;

use Closure;
use Aether\UI\Localization;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

final class SettingsForm
{
    public static function make(): array
    {
        $groups = [];
        foreach (ThemeSettings::schema() as $key => $field) {
            $component = match ($field['type']) {
                'boolean' => Toggle::make($key)->inline(false),
                'enum' => Select::make($key)->options(array_combine(array_keys($field['options']), array_map(fn (string $option) => Localization::text("options.$key.$option"), array_keys($field['options']))))->required()->native(),
                'color' => ColorPicker::make($key)->required()->regex('/^#[0-9a-fA-F]{6}$/D'),
                'integer' => TextInput::make($key)->integer()->minValue($field['min'])->maxValue($field['max'])->required(),
                default => TextInput::make($key)->maxLength($field['max']),
            };
            $component->label(Localization::text("fields.$key"))->default($field['default']);
            $component->rules([
                fn () => function (string $attribute, mixed $value, Closure $fail) use ($key): void {
                    if (!ThemeSettings::valid($key, $value ?? '')) {
                        $fail(Localization::text('validation'));
                    }
                },
            ]);
            if ($key === 'lock_mode') {
                $component->helperText(Localization::text('lock_help'));
            }
            if (in_array($key, ['login_title', 'login_message'], true)) {
                $component->helperText(Localization::text('welcome_help'));
            }
            if ($key === 'brand_name') {
                $component->helperText(Localization::text('brand_help'));
            }
            if ($key === 'console_rows') {
                $component->helperText(Localization::text('rows_help'));
            }
            $groups[$field['group']][] = $component;
        }

        return array_map(
            fn (string $group, array $fields) => Section::make(Localization::text("groups.$group"))->schema($fields)->columns(['default' => 1, 'md' => 2])->collapsible()->collapsed($group !== 'general')->columnSpanFull(),
            array_keys($groups),
            array_values($groups),
        );
    }
}
