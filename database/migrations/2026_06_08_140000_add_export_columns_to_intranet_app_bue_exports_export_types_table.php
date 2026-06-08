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

        if (! Schema::hasColumn('intranet_app_bue_exports_export_types', 'excluded_columns')) {
            Schema::table('intranet_app_bue_exports_export_types', function (Blueprint $table) {
                $table->json('excluded_columns')->nullable()->after('custom_filters');
                $table->json('column_labels')->nullable()->after('excluded_columns');
            });
        }

        ExportType::query()
            ->where('slug', 'beitragsstaerke')
            ->update([
                'excluded_columns' => ['BETRAG'],
                'column_labels' => [
                    'email' => 'E-Mail',
                    'gewerbe' => 'Gewerk',
                    'ort' => 'Ort',
                    'landkreis' => 'Landkreis',
                ],
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('intranet_app_bue_exports_export_types')) {
            return;
        }

        Schema::table('intranet_app_bue_exports_export_types', function (Blueprint $table) {
            $table->dropColumn(['excluded_columns', 'column_labels']);
        });
    }
};
