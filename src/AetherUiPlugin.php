<?php

declare(strict_types=1);

namespace Aether\UI;

use Aether\UI\Settings\SettingsForm;
use Aether\UI\Settings\SettingsStore;
use Aether\UI\Settings\ThemeSettings;
use App\Contracts\Plugins\HasPluginSettings;
use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Throwable;

final class AetherUiPlugin implements Plugin, HasPluginSettings
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
        $s = $this->getSettingsFormData();
        if (!$s['enabled']) {
            return;
        }
        $panel->colors(array_map(fn (string $color) => Color::hex($color), array_intersect_key($s, array_flip(['primary', 'secondary', 'success', 'warning', 'danger', 'info']))));
        $panel->defaultThemeMode(ThemeMode::from($s['mode']));
        if ($s['lock_mode']) {
            $panel->darkMode($s['mode'] === 'dark', isForced: $s['mode'] === 'dark')->themeSwitcher(false);
        } else {
            $panel->darkMode(true)->themeSwitcher(true);
        }
        
        if ($s['font'] !== 'inter') {
            $panel->font(Theme::font($s), provider: LocalFontProvider::class);
        }
        $panel->maxContentWidth($s['content_width'])
            ->sidebarWidth($s['sidebar_width'] . 'px')
            ->sidebarCollapsibleOnDesktop($s['sidebar_collapsible']);
        
        if ($panel->getId() !== 'app' && $s['navigation'] !== 'inherit') {
            $panel->topNavigation($s['navigation'] === 'topbar')
                ->topbar(in_array($s['navigation'], ['topbar', 'mixed'], true));
        }
        if ($s['brand_name'] !== '') {
            $panel->brandName($s['brand_name']);
        }
        if ($s['brand_logo'] !== '') {
            $panel->brandLogo($s['brand_logo']);
        }
        if ($s['brand_logo_dark'] !== '') {
            $panel->darkModeBrandLogo($s['brand_logo_dark']);
        }
        if ($s['brand_favicon'] !== '') {
            $panel->favicon($s['brand_favicon']);
        }
        $panel->brandLogoHeight($s['brand_logo_height'] . 'px');
        $panel->renderHook(PanelsRenderHook::HEAD_END, fn () => view('aether-ui::head', [
            'tokens' => Theme::css($s),
            'browserConfig' => Theme::browserConfig($s),
        ]));
        $panel->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, fn () => view('aether-ui::login', ['settings' => $s]));
        $panel->renderHook(PanelsRenderHook::FOOTER, fn () => view('aether-ui::footer', ['settings' => $s]));
    }

    public function boot(Panel $panel): void {}

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
            self::store()->write($data);
            Notification::make()->success()->title(Localization::text('saved_title'))
                ->body(Localization::text('saved_body'))->send();
        } catch (Throwable $exception) {
            report($exception);
            
            Notification::make()->danger()->title(Localization::text('failed_title'))
                ->body(Localization::text('failed_body'))->persistent()->send();
        }
    }
}
