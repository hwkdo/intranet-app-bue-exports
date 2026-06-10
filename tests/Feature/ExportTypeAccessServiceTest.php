<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Hwkdo\IntranetAppBueExports\Data\ExportTypeAccessInput;
use Hwkdo\IntranetAppBueExports\Enums\AccessModeEnum;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Hwkdo\IntranetAppBueExports\Services\ExportTypeAccessService;
use Hwkdo\IntranetAppBueExports\Support\ExportTypeValidator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

test('access service generates permission and role names from slug', function () {
    $service = app(ExportTypeAccessService::class);

    expect($service->permissionNameFromSlug('beitragsstaerke'))
        ->toBe('intranet-app-bue-exports-beitragsstaerke')
        ->and($service->roleNameFromSlug('beitragsstaerke'))
        ->toBe('Intranet-App-BuE-Exports-beitragsstaerke');
});

test('access service syncs new group with users and permissions', function () {
    Permission::findOrCreate('see-app-bue-exports', 'web');

    $user = User::factory()->create();
    $type = ExportType::factory()->create(['slug' => 'test-export']);

    app(ExportTypeAccessService::class)->syncAccess(new ExportTypeAccessInput(
        exportType: $type,
        accessMode: AccessModeEnum::NewGroup,
        selectedUserIds: [$user->id],
    ));

    $type->refresh();

    $role = Role::findByName('Intranet-App-BuE-Exports-test-export', 'web');

    expect($role)->not->toBeNull()
        ->and($role->hasPermissionTo('intranet-app-bue-exports-test-export'))->toBeTrue()
        ->and($role->hasPermissionTo('see-app-bue-exports'))->toBeTrue()
        ->and($role->users->pluck('id')->all())->toContain($user->id)
        ->and($type->access_mode)->toBe(AccessModeEnum::NewGroup)
        ->and($type->role_name)->toBe('Intranet-App-BuE-Exports-test-export');
});

test('access service attaches permission to existing role', function () {
    Permission::findOrCreate('see-app-bue-exports', 'web');

    $role = Role::create(['name' => 'Existing-Test-Role', 'guard_name' => 'web']);
    $type = ExportType::factory()->create(['slug' => 'existing-test']);

    app(ExportTypeAccessService::class)->syncAccess(new ExportTypeAccessInput(
        exportType: $type,
        accessMode: AccessModeEnum::ExistingRole,
        existingRoleName: 'Existing-Test-Role',
    ));

    $type->refresh();

    expect($role->fresh()->hasPermissionTo('intranet-app-bue-exports-existing-test'))->toBeTrue()
        ->and($type->access_mode)->toBe(AccessModeEnum::ExistingRole)
        ->and($type->role_name)->toBe('Existing-Test-Role');
});

test('access service none mode only registers permission', function () {
    $type = ExportType::factory()->create(['slug' => 'manual-test']);

    app(ExportTypeAccessService::class)->syncAccess(new ExportTypeAccessInput(
        exportType: $type,
        accessMode: AccessModeEnum::None,
    ));

    $type->refresh();

    expect(Permission::where('name', 'intranet-app-bue-exports-manual-test')->exists())->toBeTrue()
        ->and($type->access_mode)->toBe(AccessModeEnum::None)
        ->and($type->role_name)->toBeNull();
});

test('export type validator rejects invalid custom filters', function () {
    expect(fn () => ExportTypeValidator::validateCustomFilters([
        ['key' => 'invalid key', 'label' => 'X', 'field' => 'BAD', 'operator' => '>', 'type' => 'number'],
    ]))->toThrow(ValidationException::class);
});
