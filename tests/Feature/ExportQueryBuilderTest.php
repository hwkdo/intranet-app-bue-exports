<?php

declare(strict_types=1);

use Hwkdo\IntranetAppBueExports\Data\ExportFilterInput;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Hwkdo\IntranetAppBueExports\Services\ExportQueryBuilder;
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
        $table->string('ANLAGE')->nullable();
        $table->decimal('BEITRAG', 10, 2)->nullable();
    });

    DB::connection('bue_exports_test')->table('betrieb_beitragsstaerke')->insert([
        ['EMAIL' => 'a@test.de', 'GEWERBE' => 'Tischler', 'ORT' => 'Dortmund', 'LANDKREIS' => 'DO', 'ANLAGE' => 'A1', 'BEITRAG' => 500],
        ['EMAIL' => null, 'GEWERBE' => 'Maler', 'ORT' => 'Dortmund', 'LANDKREIS' => 'DO', 'ANLAGE' => 'A2', 'BEITRAG' => 100],
        ['EMAIL' => 'b@test.de', 'GEWERBE' => 'Tischler', 'ORT' => 'Essen', 'LANDKREIS' => 'E', 'ANLAGE' => 'A1', 'BEITRAG' => 800],
    ]);
});

function makeBeitragsstaerkeExportType(): ExportType
{
    return ExportType::factory()->create([
        'slug' => 'beitragsstaerke-test',
        'oracle_view' => 'betrieb_beitragsstaerke',
        'email_field' => 'EMAIL',
        'gewerke_field' => 'GEWERBE',
        'orte_field' => 'ORT',
        'landkreise_field' => 'LANDKREIS',
        'anlage_field' => 'ANLAGE',
        'custom_filters' => [
            ['key' => 'min_betrag', 'label' => 'Min', 'field' => 'BEITRAG', 'operator' => '>', 'type' => 'number'],
            ['key' => 'max_betrag', 'label' => 'Max', 'field' => 'BEITRAG', 'operator' => '<', 'type' => 'number'],
        ],
    ]);
}

test('export query builder or-combines ort and landkreis filters', function () {
    $type = makeBeitragsstaerkeExportType();

    $query = app(ExportQueryBuilder::class)->build($type, new ExportFilterInput(
        orte: ['Dortmund'],
        landkreise: ['E'],
    ));

    $results = $query->get();

    expect($results)->toHaveCount(3)
        ->and($results->pluck('ORT')->all())->toContain('Dortmund', 'Essen');
});

test('export query builder applies anlage filter', function () {
    $type = makeBeitragsstaerkeExportType();

    $query = app(ExportQueryBuilder::class)->build($type, new ExportFilterInput(
        anlage: 'A2',
    ));

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->ANLAGE)->toBe('A2');
});

test('export query builder applies email gewerke and ort filters', function () {
    $type = makeBeitragsstaerkeExportType();

    $query = app(ExportQueryBuilder::class)->build($type, new ExportFilterInput(
        nurMitEmail: true,
        gewerke: ['Tischler'],
        orte: ['Dortmund'],
    ));

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->EMAIL)->toBe('a@test.de');
});

test('export query builder applies custom numeric filters', function () {
    $type = makeBeitragsstaerkeExportType();

    $query = app(ExportQueryBuilder::class)->build($type, new ExportFilterInput(
        custom: ['min_betrag' => 600],
    ));

    expect($query->get())->toHaveCount(1)
        ->and($query->get()->first()->BEITRAG)->toEqual(800);
});

test('export query builder limits results to export type max records', function () {
    $type = makeBeitragsstaerkeExportType();
    $type->update(['max_records' => 1]);

    $query = app(ExportQueryBuilder::class)->build($type, new ExportFilterInput);

    expect($query->get())->toHaveCount(1);
});

test('export query builder uses per export max records override', function () {
    $type = makeBeitragsstaerkeExportType();
    $type->update(['max_records' => 10_000]);

    $query = app(ExportQueryBuilder::class)->build($type, new ExportFilterInput(
        maxRecords: 2,
    ));

    expect($query->get())->toHaveCount(2);
});

test('export query builder caps per export override at export type maximum', function () {
    $type = makeBeitragsstaerkeExportType();
    $type->update(['max_records' => 2]);

    $query = app(ExportQueryBuilder::class)->build($type, new ExportFilterInput(
        maxRecords: 99,
    ));

    expect($query->get())->toHaveCount(2);
});
