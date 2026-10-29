<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('aether_ui_settings')) {
            return;
        }
        Schema::create('aether_ui_settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->text('value');
            $table->timestamp('updated_at')->nullable();
        });
        $legacy = storage_path('app/aether-ui/settings.json');
        if (is_file($legacy)) {
            $json = file_get_contents($legacy);
            if ($json !== false && strlen($json) <= 65536) {
                $data = json_decode($json, true);
                if (is_array($data) && ($data['schema_version'] ?? null) === 1 && is_array($data['settings'] ?? null)) {
                    DB::table('aether_ui_settings')->insert(['key' => 'settings', 'value' => $json, 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        // Preserve administrator settings.
    }
};
