<?php

declare(strict_types=1);

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

        if (Schema::hasColumn('intranet_app_bue_exports_export_types', 'max_records')) {
            return;
        }

        Schema::table('intranet_app_bue_exports_export_types', function (Blueprint $table) {
            $table->unsignedInteger('max_records')
                ->default(10_000)
                ->after('sort_order');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('intranet_app_bue_exports_export_types')) {
            return;
        }

        Schema::table('intranet_app_bue_exports_export_types', function (Blueprint $table) {
            $table->dropColumn('max_records');
        });
    }
};
