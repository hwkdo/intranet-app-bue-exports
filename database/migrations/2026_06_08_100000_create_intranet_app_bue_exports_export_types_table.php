<?php

declare(strict_types=1);

use Hwkdo\IntranetAppBueExports\Enums\AccessModeEnum;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Hwkdo\IntranetAppBueExports\Services\ExportTypeAccessService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('intranet_app_bue_exports_export_types')) {
            return;
        }

        Schema::create('intranet_app_bue_exports_export_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('oracle_view');
            $table->string('email_field')->nullable();
            $table->string('gewerke_field')->nullable();
            $table->string('orte_field')->nullable();
            $table->string('landkreise_field')->nullable();
            $table->json('custom_filters')->nullable();
            $table->json('excluded_columns')->nullable();
            $table->json('column_labels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('max_records')->default(10_000);
            $table->string('permission_name')->unique();
            $table->string('access_mode')->default(AccessModeEnum::None->value);
            $table->string('role_name')->nullable();
            $table->timestamps();
        });

        if (ExportType::query()->where('slug', 'beitragsstaerke')->exists()) {
            return;
        }

        $accessService = app(ExportTypeAccessService::class);
        $slug = 'beitragsstaerke';
        $permissionName = $accessService->registerPermission($slug);

        ExportType::query()->create([
            'name' => 'Beitragsstärke',
            'slug' => $slug,
            'oracle_view' => 'hwkuserro.betrieb_beitragstaerke',
            'email_field' => 'EMAIL',
            'gewerke_field' => 'GEWERBE',
            'orte_field' => 'ORT',
            'landkreise_field' => 'LANDKREIS',
            'custom_filters' => [
                [
                    'key' => 'min_betrag',
                    'label' => 'Mindest-Beitrag Jährlich',
                    'field' => 'BETRAG',
                    'operator' => '>',
                    'type' => 'number',
                ],
                [
                    'key' => 'max_betrag',
                    'label' => 'Maximal-Beitrag Jährlich',
                    'field' => 'BETRAG',
                    'operator' => '<',
                    'type' => 'number',
                ],
            ],
            'excluded_columns' => ['BETRAG'],
            'column_labels' => [
                'email' => 'E-Mail',
                'gewerbe' => 'Gewerk',
                'ort' => 'Ort',
                'landkreis' => 'Landkreis',
            ],
            'is_active' => true,
            'sort_order' => 1,
            'max_records' => 10_000,
            'permission_name' => $permissionName,
            'access_mode' => AccessModeEnum::None->value,
            'role_name' => null,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_app_bue_exports_export_types');
    }
};
