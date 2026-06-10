<?php

declare(strict_types=1);

use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ExportType::query()
            ->where('slug', 'beitragsstaerke')
            ->update([
                'custom_filters' => [
                    [
                        'key' => 'min_betrag',
                        'label' => 'Mindest-Beitrag Jährlich',
                        'field' => 'BEITRAG',
                        'operator' => '>',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'max_betrag',
                        'label' => 'Maximal-Beitrag Jährlich',
                        'field' => 'BEITRAG',
                        'operator' => '<',
                        'type' => 'number',
                    ],
                ],
                'excluded_columns' => ['BEITRAG'],
            ]);
    }

    public function down(): void
    {
        ExportType::query()
            ->where('slug', 'beitragsstaerke')
            ->update([
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
            ]);
    }
};
