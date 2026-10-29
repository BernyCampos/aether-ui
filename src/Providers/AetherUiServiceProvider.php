<?php

declare(strict_types=1);

namespace Aether\UI\Providers;

use Aether\UI\AetherUiPlugin;
use App\Filament\Server\Resources\Files\Pages\ListFiles;
use App\Filament\Server\Resources\Files\Pages\SearchFiles;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

final class AetherUiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Table::configureUsing(function (Table $table): void {
            if ((new AetherUiPlugin())->getSettingsFormData()['enabled']) {
                $files = $table->getLivewire() instanceof ListFiles || $table->getLivewire() instanceof SearchFiles;
                $table->stackedOnMobile(!$files);
                if ($files) {
                    $table->extraAttributes(['class' => 'aether-file-table'], merge: true);
                }
            }
        });
    }
}
