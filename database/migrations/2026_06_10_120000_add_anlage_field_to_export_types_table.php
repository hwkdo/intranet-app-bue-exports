<?php

declare(strict_types=1);

use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('intranet_app_bue_exports_export_types')) {
            return;
        }

        if (! Schema::hasColumn('intranet_app_bue_exports_export_types', 'anlage_field')) {
            Schema::table('intranet_app_bue_exports_export_types', function (Blueprint $table) {
                $table->string('anlage_field')->nullable()->after('landkreise_field');
            });
        }

        $type = ExportType::query()->where('slug', 'beitragsstaerke')->first();

        if ($type === null) {
            return;
        }

        $columnLabels = $type->column_labels ?? [];
        $columnLabels['anlage'] = 'Anlage';

        $type->update([
            'anlage_field' => 'ANLAGE',
            'column_labels' => $columnLabels,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('intranet_app_bue_exports_export_types')) {
            return;
        }

        $type = ExportType::query()->where('slug', 'beitragsstaerke')->first();

        if ($type !== null) {
            $columnLabels = $type->column_labels ?? [];
            unset($columnLabels['anlage']);

            $type->update([
                'anlage_field' => null,
                'column_labels' => $columnLabels,
            ]);
        }

        if (Schema::hasColumn('intranet_app_bue_exports_export_types', 'anlage_field')) {
            Schema::table('intranet_app_bue_exports_export_types', function (Blueprint $table) {
                $table->dropColumn('anlage_field');
            });
        }
    }
};
