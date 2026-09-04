<?php

declare(strict_types=1);

use App\Models\User;
use Hwkdo\IntranetAppBase\Search\ActionsSearchSource;
use Hwkdo\IntranetAppBueExports\IntranetAppBueExports;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Volt\Volt;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Permission::findOrCreate('see-app-bue-exports', 'web');
    Permission::findOrCreate('manage-app-bue-exports', 'web');
});

test('export type without filters does not require form', function (): void {
    $type = ExportType::factory()->withoutFilters()->make();

    expect($type->requiresExportForm())->toBeFalse();
});

test('export type with email field only does not require form', function (): void {
    $type = ExportType::factory()->withoutFilters()->make([
        'email_field' => 'EMAIL',
    ]);

    expect($type->requiresExportForm())->toBeFalse();
});

test('export type with stamm or custom filters requires form', function (): void {
    expect(ExportType::factory()->make()->requiresExportForm())->toBeTrue()
        ->and(ExportType::factory()->withoutFilters()->make([
            'custom_filters' => [
                ['key' => 'min', 'label' => 'Min', 'field' => 'BETRAG', 'operator' => '>', 'type' => 'number'],
            ],
        ])->requiresExportForm())->toBeTrue();
});

test('search actions are generated for active export types', function (): void {
    $direct = ExportType::factory()->withoutFilters()->create([
        'name' => 'Konjunkturumfrage',
        'slug' => 'konjunkturumfrage',
        'permission_name' => 'intranet-app-bue-exports-konjunkturumfrage',
        'sort_order' => 1,
    ]);
    $form = ExportType::factory()->create([
        'name' => 'Beitragsstärke',
        'slug' => 'beitragsstaerke',
        'permission_name' => 'intranet-app-bue-exports-beitragsstaerke',
        'sort_order' => 2,
    ]);
    ExportType::factory()->withoutFilters()->create([
        'name' => 'Inaktiv',
        'slug' => 'inaktiv',
        'permission_name' => 'intranet-app-bue-exports-inaktiv',
        'is_active' => false,
    ]);

    $actions = collect(IntranetAppBueExports::searchActions())->keyBy('key');

    expect($actions)->toHaveCount(2)
        ->and($actions->get('bue-exports.export.konjunkturumfrage'))->not->toBeNull()
        ->and($actions->get('bue-exports.export.konjunkturumfrage')->download)->toBeTrue()
        ->and($actions->get('bue-exports.export.konjunkturumfrage')->routeName)->toBe('apps.bue-exports.export.download')
        ->and($actions->get('bue-exports.export.konjunkturumfrage')->url())
        ->toBe(route('apps.bue-exports.export.download', ['exportType' => $direct->slug]))
        ->and($actions->get('bue-exports.export.beitragsstaerke'))->not->toBeNull()
        ->and($actions->get('bue-exports.export.beitragsstaerke')->download)->toBeFalse()
        ->and($actions->get('bue-exports.export.beitragsstaerke')->routeName)->toBe('apps.bue-exports.export')
        ->and($actions->get('bue-exports.export.beitragsstaerke')->url())
        ->toBe(route('apps.bue-exports.export', ['type' => $form->slug]));
});

test('actions search source finds direct export when permitted', function (): void {
    Permission::findOrCreate('intranet-app-bue-exports-konjunkturumfrage', 'web');

    ExportType::factory()->withoutFilters()->create([
        'name' => 'Konjunkturumfrage',
        'slug' => 'konjunkturumfrage',
        'permission_name' => 'intranet-app-bue-exports-konjunkturumfrage',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo(['see-app-bue-exports', 'intranet-app-bue-exports-konjunkturumfrage']);

    $result = app(ActionsSearchSource::class)
        ->search('konjunktur', $user, 10)
        ->firstWhere('title', 'Export: Konjunkturumfrage');

    expect($result)->not->toBeNull()
        ->and($result->download)->toBeTrue()
        ->and($result->url)->toBe(route('apps.bue-exports.export.download', ['exportType' => 'konjunkturumfrage']));
});

test('export page preselects type from query string', function (): void {
    Permission::findOrCreate('intranet-app-bue-exports-beitragsstaerke', 'web');

    $type = ExportType::factory()->create([
        'name' => 'Beitragsstärke',
        'slug' => 'beitragsstaerke',
        'permission_name' => 'intranet-app-bue-exports-beitragsstaerke',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo(['see-app-bue-exports', 'intranet-app-bue-exports-beitragsstaerke']);

    Volt::withQueryParams(['type' => 'beitragsstaerke'])
        ->test('apps.bue-exports.export.index')
        ->actingAs($user)
        ->assertSet('exportTypeId', $type->id)
        ->assertSet('maxRecords', $type->max_records);
});

test('download route redirects form exports to the filter page', function (): void {
    Permission::findOrCreate('intranet-app-bue-exports-beitragsstaerke', 'web');

    $type = ExportType::factory()->create([
        'slug' => 'beitragsstaerke',
        'permission_name' => 'intranet-app-bue-exports-beitragsstaerke',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo(['see-app-bue-exports', 'intranet-app-bue-exports-beitragsstaerke']);

    actingAs($user)
        ->get(route('apps.bue-exports.export.download', $type))
        ->assertRedirect(route('apps.bue-exports.export', ['type' => 'beitragsstaerke']));
});

test('download route streams excel for parameterless export types', function (): void {
    Excel::fake();

    Config::set('intranet-app-bue-exports.bue_connection.name', 'bue_exports_test');
    Config::set('database.connections.bue_exports_test', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    Schema::connection('bue_exports_test')->create('konjunktur', function ($table): void {
        $table->string('NAME')->nullable();
        $table->string('EMAIL')->nullable();
    });

    DB::connection('bue_exports_test')->table('konjunktur')->insert([
        ['NAME' => 'Betrieb A', 'EMAIL' => 'a@test.de'],
    ]);

    Permission::findOrCreate('intranet-app-bue-exports-konjunkturumfrage', 'web');

    $type = ExportType::factory()->withoutFilters()->create([
        'slug' => 'konjunkturumfrage',
        'oracle_view' => 'konjunktur',
        'permission_name' => 'intranet-app-bue-exports-konjunkturumfrage',
        'max_records' => 100,
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo(['see-app-bue-exports', 'intranet-app-bue-exports-konjunkturumfrage']);

    $this->travelTo(now());

    actingAs($user)
        ->get(route('apps.bue-exports.export.download', $type))
        ->assertOk();

    Excel::assertDownloaded($type->slug.'-'.now()->format('Y-m-d-His').'.xlsx');
});

test('download route forbids users without export type permission', function (): void {
    Permission::findOrCreate('intranet-app-bue-exports-konjunkturumfrage', 'web');

    $type = ExportType::factory()->withoutFilters()->create([
        'slug' => 'konjunkturumfrage',
        'permission_name' => 'intranet-app-bue-exports-konjunkturumfrage',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('see-app-bue-exports');

    actingAs($user)
        ->get(route('apps.bue-exports.export.download', $type))
        ->assertForbidden();
});
