<?php

declare(strict_types=1);

use App\Models\User;
use Hwkdo\IntranetAppBueExports\Enums\AccessModeEnum;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Permission::findOrCreate('see-app-bue-exports', 'web');
    Permission::findOrCreate('manage-app-bue-exports', 'web');
});

test('user without export permission cannot see export type in accessible scope', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('see-app-bue-exports');

    ExportType::factory()->create([
        'slug' => 'hidden-export',
        'permission_name' => 'intranet-app-bue-exports-hidden-export',
        'is_active' => true,
    ]);

    actingAs($user);

    expect(ExportType::query()->where('is_active', true)->accessibleBy()->count())->toBe(0);
});

test('user with export permission can see export type', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('intranet-app-bue-exports-visible-export', 'web');
    $user->givePermissionTo(['see-app-bue-exports', 'intranet-app-bue-exports-visible-export']);

    ExportType::factory()->create([
        'slug' => 'visible-export',
        'permission_name' => 'intranet-app-bue-exports-visible-export',
        'is_active' => true,
    ]);

    actingAs($user);

    expect(ExportType::query()->where('is_active', true)->accessibleBy()->count())->toBe(1);
});

test('admin can access export types admin page', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(['see-app-bue-exports', 'manage-app-bue-exports']);

    actingAs($admin)
        ->get(route('apps.bue-exports.admin.export-types'))
        ->assertOk();
});

test('admin can create export type via livewire', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(['see-app-bue-exports', 'manage-app-bue-exports']);

    Volt::test('apps.bue-exports.admin.export-types')
        ->actingAs($admin)
        ->call('create')
        ->set('name', 'Test Export')
        ->set('slug', 'test_export')
        ->set('oracle_view', 'hwkuserro.test_view')
        ->set('max_records', 5000)
        ->set('accessMode', AccessModeEnum::None->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(ExportType::where('slug', 'test_export')->exists())->toBeTrue()
        ->and(ExportType::where('slug', 'test_export')->value('max_records'))->toBe(5000)
        ->and(Permission::where('name', 'intranet-app-bue-exports-test_export')->exists())->toBeTrue();
});

test('admin can save excluded columns via livewire', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(['see-app-bue-exports', 'manage-app-bue-exports']);

    Volt::test('apps.bue-exports.admin.export-types')
        ->actingAs($admin)
        ->call('create')
        ->set('name', 'Spalten Export')
        ->set('slug', 'spalten_export')
        ->set('oracle_view', 'hwkuserro.test_view')
        ->set('excludedColumnsInput', 'betrag, INTERN_ID')
        ->set('accessMode', AccessModeEnum::None->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(ExportType::where('slug', 'spalten_export')->value('excluded_columns'))
        ->toBe(['BETRAG', 'INTERN_ID']);
});

test('new group mode creates role with selected users', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $admin->givePermissionTo(['see-app-bue-exports', 'manage-app-bue-exports']);

    Volt::test('apps.bue-exports.admin.export-types')
        ->actingAs($admin)
        ->call('create')
        ->set('name', 'Gruppen Export')
        ->set('slug', 'gruppen_export')
        ->set('oracle_view', 'hwkuserro.test_view')
        ->set('accessMode', AccessModeEnum::NewGroup->value)
        ->set('selectedUserIds', [$member->id])
        ->call('save')
        ->assertHasNoErrors();

    $type = ExportType::where('slug', 'gruppen_export')->first();

    expect($type->access_mode)->toBe(AccessModeEnum::NewGroup)
        ->and($member->fresh()->hasRole('Intranet-App-BuE-Exports-gruppen_export'))->toBeTrue();
});
