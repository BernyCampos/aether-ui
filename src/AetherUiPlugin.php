<?php

declare(strict_types=1);

namespace Aether\UI;

use Aether\UI\Settings\SettingsForm;
use Aether\UI\Settings\SettingsStore;
use Aether\UI\Settings\ThemeSettings;
use App\Contracts\Plugins\HasPluginSettings;
use App\Contracts\Plugins\HasTheme;
use Filament\Contracts\Plugin;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Throwable;

final class AetherUiPlugin implements Plugin, HasTheme, HasPluginSettings
{
    public function getId(): string
    {
        return 'aether-ui';
    }

    public static function store(): SettingsStore
    {
        return new SettingsStore(storage_path('app/aether-ui/settings.json'));
    }

    public function register(Panel $panel): void
    {
        // Do not mutate shared Filament panels. Pelican's ThemeService
        // activates HasTheme resources only for the selected theme.
    }

    public function boot(Panel $panel): void {}

    public function getThemeViteEntry(): string|array|null
    {
        return 'plugins/aether-ui/resources/css/aether.css';
    }

    public function getThemeColors(): array
    {
        $settings = $this->getSettingsFormData();

        return array_map(
            static fn (string $hex): array => Color::hex($hex),
            array_intersect_key($settings, array_flip(['primary', 'secondary', 'success', 'warning', 'danger', 'info'])),
        );
    }

    public function getThemeFont(): ?string
    {
        // Existing fonts are applied through Aether's scoped CSS tokens.
        return null;
    }

    public function getThemeRenderHooks(): array
    {
        return [
            PanelsRenderHook::HEAD_END => function (): string {
                $settings = $this->getSettingsFormData();

                return view('aether-ui::head', [
                    'tokens' => Theme::css($settings),
                    'browserConfig' => Theme::browserConfig($settings),
                ])->render();
            },
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE => function (): string {
                return view('aether-ui::login', ['settings' => $this->getSettingsFormData()])->render();
            },
            PanelsRenderHook::FOOTER => function (): string {
                return view('aether-ui::footer', ['settings' => $this->getSettingsFormData()])->render();
            },
        ];
    }

    public function getSettingsFormData(): array
    {
        try {
            return self::store()->read();
        } catch (Throwable $exception) {
            report($exception);

            
            return ThemeSettings::defaults();
        }
    }

    public function getSettingsForm(): array
    {
        return SettingsForm::make();
    }

    public function saveSettings(array $data): void
    {
        abort_unless(auth()->user()?->can('update plugin'), 403);
        try {
            // Preserve legacy settings now controlled by the native picker.
            self::store()->write(array_replace($this->getSettingsFormData(), $data));
            Notification::make()->success()->title(Localization::text('saved_title'))
                ->body(Localization::text('saved_body'))->send();
        } catch (Throwable $exception) {
            report($exception);
            
            Notification::make()->danger()->title(Localization::text('failed_title'))
                ->body(Localization::text('failed_body'))->persistent()->send();
        }
    }
}
