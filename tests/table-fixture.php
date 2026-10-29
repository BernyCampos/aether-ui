<?php

declare(strict_types=1);

$root = getenv('AETHER_TEST_PANEL');
$output = getenv('AETHER_TABLE_OUTPUT');
if (!$root || !is_file($root . '/.aether-isolated-test') || !$output || !is_dir($output)) {
    throw new RuntimeException('Set AETHER_TEST_PANEL to a marked disposable panel and AETHER_TABLE_OUTPUT to an existing output directory.');
}
chdir($root);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));

class AetherTableFixture extends Livewire\Component implements Filament\Tables\Contracts\HasTable, Filament\Schemas\Contracts\HasSchemas, Filament\Actions\Contracts\HasActions
{
    use Filament\Tables\Concerns\InteractsWithTable;
    use Filament\Schemas\Concerns\InteractsWithSchemas;
    use Filament\Actions\Concerns\InteractsWithActions;

    public string $kind = 'files';

    public function table(Filament\Tables\Table $table): Filament\Tables\Table
    {
        $column = fn (string $name) => Filament\Tables\Columns\TextColumn::make($name)->label(ucfirst(str_replace('_', ' ', $name)));
        if ($this->kind === 'files') {
            $records = [];
            foreach (['.cache', '.local', '.mixin.out', 'automodpack', 'config', 'crash-reports', 'customnpcs', 'server.properties', str_repeat('long-file-name-', 14) . '.json'] as $id => $name) {
                $records[$id + 1] = ['name' => $name, 'size' => $id < 7 ? '' : '12.5 KiB', 'modified_at' => '1 month ago'];
            }
            return $table->extraAttributes(['class' => 'aether-file-table'])->stackedOnMobile(false)->paginated(false)->records(fn () => $records)->selectable()->columns([
                $column('name')->sortable()->icon('heroicon-o-folder'),
                $column('size')->sortable()->visibleFrom('md'),
                $column('modified_at')->sortable()->visibleFrom('md'),
            ])->recordActions([
                Filament\Actions\Action::make('open')->label('Open')->icon('heroicon-o-eye')->iconButton(),
                Filament\Actions\ActionGroup::make([Filament\Actions\Action::make('rename')->label('Rename')])->icon('heroicon-o-ellipsis-vertical'),
                Filament\Actions\Action::make('delete')->label('Delete')->icon('heroicon-o-trash')->iconButton()->color('danger'),
            ]);
        }
        $records = $this->kind === 'empty' ? [] : [1 => ['number' => '1', 'title' => 'Unable to connect to a server with an unusually long workspace name', 'category' => 'Technical support', 'priority' => 'Normal', 'status' => 'Open', 'assigned_to' => 'Support team', 'created_by' => 'Example user', 'created_at' => '1 day ago']];
        return $table->stackedOnMobile()->paginated(false)->records(fn () => $records)->emptyStateHeading('No tickets')->columns([
            $column('number')->label('#')->sortable(),
            $column('title')->grow()->sortable(),
            $column('category'), $column('priority'), $column('status'), $column('assigned_to'), $column('created_by'), $column('created_at')->sortable(),
        ]);
    }

    public function render(): Illuminate\Contracts\View\View
    {
        return view()->file(__DIR__ . '/table-fixture.blade.php');
    }
}

Livewire\Livewire::component('aether-table-fixture', AetherTableFixture::class);
foreach (['files', 'empty', 'tickets'] as $kind) {
    file_put_contents($output . '/table-' . $kind . '.html', Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles() . Livewire\Livewire::mount('aether-table-fixture', ['kind' => $kind]));
}
echo "Native Filament table fixtures rendered.\n";
