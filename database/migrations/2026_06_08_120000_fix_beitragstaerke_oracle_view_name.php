<?php

declare(strict_types=1);

use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ExportType::query()
            ->where('oracle_view', 'hwkuserro.betrieb_beitragsstaerke')
            ->update(['oracle_view' => 'hwkuserro.betrieb_beitragstaerke']);
    }

    public function down(): void
    {
        ExportType::query()
            ->where('oracle_view', 'hwkuserro.betrieb_beitragstaerke')
            ->update(['oracle_view' => 'hwkuserro.betrieb_beitragsstaerke']);
    }
};
