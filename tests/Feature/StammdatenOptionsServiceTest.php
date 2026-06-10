<?php

declare(strict_types=1);

use Hwkdo\IntranetAppBueExports\Services\StammdatenOptionsService;
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

    Config::set('intranet-app-bue-exports.labeled_stamm_views.anlagen', [
        'view' => 'stamm_anlagen',
        'value_column' => 'ANLAGE',
        'label_column' => 'ANLAGEBEZEICHNUNG',
    ]);

    Schema::connection('bue_exports_test')->create('stamm_anlagen', function ($table) {
        $table->string('ANLAGE');
        $table->string('ANLAGEBEZEICHNUNG');
    });

    DB::connection('bue_exports_test')->table('stamm_anlagen')->insert([
        ['ANLAGE' => 'A2', 'ANLAGEBEZEICHNUNG' => 'Zweite Anlage'],
        ['ANLAGE' => 'A1', 'ANLAGEBEZEICHNUNG' => 'Erste Anlage'],
    ]);
});

test('stammdaten options service returns anlagen with value and label', function () {
    $options = app(StammdatenOptionsService::class)->anlagen();

    expect($options)->toHaveCount(2)
        ->and($options->first())->toBe([
            'value' => 'A1',
            'label' => 'Erste Anlage',
        ])
        ->and($options->last())->toBe([
            'value' => 'A2',
            'label' => 'Zweite Anlage',
        ]);
});
