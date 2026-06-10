<?php

declare(strict_types=1);

use Hwkdo\IntranetAppBueExports\Exports\BueQueryExport;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Hwkdo\IntranetAppBueExports\Services\ExportColumnResolver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Config::set('intranet-app-bue-exports.bue_connection.name', 'bue_exports_test');

    Config::set('database.connections.bue_exports_test', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    Schema::connection('bue_exports_test')->create('betrieb_beitragsstaerke', function ($table) {
        $table->string('EMAIL')->nullable();
        $table->string('GEWERBE')->nullable();
        $table->string('ORT')->nullable();
        $table->string('LANDKREIS')->nullable();
        $table->decimal('BEITRAG', 10, 2)->nullable();
    });

    DB::connection('bue_exports_test')->table('betrieb_beitragsstaerke')->insert([
        ['EMAIL' => 'a@test.de', 'GEWERBE' => 'Tischler', 'ORT' => 'Dortmund', 'LANDKREIS' => 'DO', 'BEITRAG' => 500],
    ]);
});

test('export column resolver applies labels and excludes configured columns', function () {
    $type = ExportType::factory()->create([
        'excluded_columns' => ['BEITRAG'],
        'column_labels' => [
            'email' => 'E-Mail',
            'gewerbe' => 'Gewerk',
            'ort' => 'Ort',
            'landkreis' => 'Landkreis',
        ],
    ]);

    $query = DB::connection('bue_exports_test')->table('betrieb_beitragsstaerke');

    $resolved = app(ExportColumnResolver::class)->resolve($type, $query);

    expect($resolved['keys'])->not->toContain('BEITRAG')
        ->and($resolved['headings'])->toBe(['E-Mail', 'Gewerk', 'Ort', 'Landkreis']);
});

test('bue query export maps only configured export columns', function () {
    $type = ExportType::factory()->create([
        'excluded_columns' => ['BEITRAG'],
        'column_labels' => [
            'EMAIL' => 'E-Mail',
            'GEWERBE' => 'Gewerk',
            'ORT' => 'Ort',
            'LANDKREIS' => 'Landkreis',
        ],
    ]);

    $query = DB::connection('bue_exports_test')->table('betrieb_beitragsstaerke');
    $export = new BueQueryExport($query, $type);

    $row = (object) ['EMAIL' => 'a@test.de', 'GEWERBE' => 'Tischler', 'ORT' => 'Dortmund', 'LANDKREIS' => 'DO', 'BEITRAG' => 500];

    expect($export->headings())->toBe(['E-Mail', 'Gewerk', 'Ort', 'Landkreis'])
        ->and($export->map($row))->toBe(['a@test.de', 'Tischler', 'Dortmund', 'DO']);
});
