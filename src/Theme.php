<?php

declare(strict_types=1);

namespace Aether\UI;

use Aether\UI\Settings\ThemeSettings;

final class Theme
{
    public static function font(array $settings): string
    {
        return match ($settings['font']) {
            'inter' => 'Inter Variable, system-ui, sans-serif',
            'serif' => 'Georgia, serif',
            'mono' => 'ui-monospace, Consolas, monospace',
            'custom' => '"' . $settings['custom_font'] . '", system-ui, sans-serif',
            default => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        };
    }

    public static function css(array $input): string
    {
        
        $s = ThemeSettings::normalize($input, false);
        $vars = ['font' => self::font($s), 'line-height' => $s['line_height'] / 100];
        foreach (['primary', 'secondary', 'success', 'warning', 'danger', 'info'] as $color) {
            $vars[$color] = $s[$color];
        }
        foreach (['font_size', 'spacing', 'radius', 'control_radius', 'border_width', 'nav_radius', 'card_padding', 'console_radius', 'login_width'] as $key) {
            $vars[str_replace('_', '-', $key)] = $s[$key] . 'px';
        }
        $vars['blur'] = $s['glass'] ? $s['blur'] . 'px' : '0px';
        $vars['glass-opacity'] = $s['glass'] ? '86%' : '100%';
        $vars['duration'] = $s['motion'] ? '160ms' : '0ms';
        $vars['shadow'] = match ($s['shadow']) {
            'none' => 'none',
            'strong' => '0 18px 48px rgb(0 0 0 / 0.22)',
            default => '0 8px 30px rgb(15 23 42 / 0.08)',
        };
        $vars['card-shadow'] = $s['card_style'] === 'elevated' ? 'var(--aether-shadow)' : 'none';
        $vars['card-border'] = $s['card_style'] === 'flat' ? '0px' : $s['border_width'] . 'px';
        $vars['nav-indicator'] = $s['nav_indicator'] ? '3px' : '0px';
        $vars['hover-color'] = $s['card_hover'] ? 'var(--aether-primary)' : 'var(--aether-border)';
        $vars['ambient'] = $s['gradient']
            ? 'radial-gradient(ellipse at 90% 0%, color-mix(in srgb, var(--aether-primary) 12%, transparent), transparent 55%), radial-gradient(ellipse at 0% 80%, color-mix(in srgb, var(--aether-secondary) 8%, transparent), transparent 50%)'
            : 'none';
        $vars['console-background'] = $s['console_background'];
        $vars['console-foreground'] = $s['console_foreground'];
        $vars['login-image'] = $s['login_image'] !== '' ? 'url("' . $s['login_image'] . '")' : 'none';
        $serialize = fn (array $values) => implode('', array_map(fn ($key, $value) => "--aether-$key:$value;", array_keys($values), array_values($values)));
        $css = 'html.aether-ui{' . $serialize($vars) . '}';
        foreach (['light' => 'html.aether-ui', 'dark' => 'html.aether-ui.dark'] as $mode => $selector) {
            $palette = [];
            foreach (['background', 'surface', 'text', 'muted', 'border'] as $color) {
                $palette[$color] = $s[$mode . '_' . $color];
            }
            $css .= $selector . '{' . $serialize($palette) . '}';
        }
        if ($s['font'] === 'custom' && $s['font_url'] !== '') {
            $css .= '@font-face{font-family:"' . $s['custom_font'] . '";src:url("' . $s['font_url'] . '") format("woff2");font-display:swap;}';
        }

        return $css;
    }

    public static function browserConfig(array $input): array
    {
        $s = ThemeSettings::normalize($input, false);
        $theme = [];
        foreach (['background', 'foreground', 'cursor', 'black', 'red', 'green', 'yellow', 'blue', 'magenta', 'cyan', 'white', 'brightBlack', 'brightRed', 'brightGreen', 'brightYellow', 'brightBlue', 'brightMagenta', 'brightCyan', 'brightWhite'] as $key) {
            $theme[$key] = $s['console_' . $key];
        }
        $theme['selectionBackground'] = $s['console_selection'];

        return [
            'enabled' => $s['enabled'],
            'loginLayout' => $s['login_layout'],
            'consoleEnabled' => $s['console_enabled'],
            'terminal' => [
                'theme' => $theme,
                'fontFamily' => match ($s['console_font']) {
                    'consolas' => 'Consolas, monospace',
                    'courier' => '"Courier New", monospace',
                    default => 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
                },
                'fontSize' => $s['console_font_size'],
                'lineHeight' => $s['console_line_height'] / 100,
                'rows' => $s['console_rows'],
                'cursorStyle' => $s['console_cursor_style'],
                'cursorBlink' => $s['console_cursor_blink'] && $s['motion'],
            ],
        ];
    }
}
