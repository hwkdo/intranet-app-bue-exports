<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Services;

use App\Models\Role;
use Hwkdo\IntranetAppBueExports\Data\ExportTypeAccessInput;
use Hwkdo\IntranetAppBueExports\Enums\AccessModeEnum;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ExportTypeAccessService
{
    public function permissionNameFromSlug(string $slug): string
    {
        return 'intranet-app-bue-exports-'.$slug;
    }

    public function roleNameFromSlug(string $slug): string
    {
        return 'Intranet-App-BuE-Exports-'.$slug;
    }

    public function registerPermission(string $slug): string
    {
        $name = $this->permissionNameFromSlug($slug);
        Permission::findOrCreate($name, 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $name;
    }

    public function syncAccess(ExportTypeAccessInput $input): void
    {
        $exportType = $input->exportType;
        $slug = $exportType->slug;

        $permissionName = $this->registerPermission($slug);

        $exportType->permission_name = $permissionName;

        match ($input->accessMode) {
            AccessModeEnum::NewGroup => $this->syncNewGroup($exportType, $input->selectedUserIds),
            AccessModeEnum::ExistingRole => $this->syncExistingRole($exportType, $input->existingRoleName),
            AccessModeEnum::None => $this->syncNone($exportType),
        };

        $exportType->access_mode = $input->accessMode;
        $exportType->save();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<int>  $userIds
     */
    private function syncNewGroup(ExportType $exportType, array $userIds): void
    {
        $roleName = $this->roleNameFromSlug($exportType->slug);
        $role = Role::findOrCreate($roleName, 'web');

        $this->giveAppPermissionsToRole($role, $exportType->permission_name);

        $role->users()->sync($userIds);

        $exportType->role_name = $roleName;
    }

    private function syncExistingRole(ExportType $exportType, ?string $existingRoleName): void
    {
        if ($existingRoleName === null || $existingRoleName === '') {
            throw ValidationException::withMessages([
                'existingRoleName' => 'Bitte wählen Sie eine Rolle aus.',
            ]);
        }

        $role = Role::findByName($existingRoleName, 'web');

        $this->giveAppPermissionsToRole($role, $exportType->permission_name);

        $exportType->role_name = $existingRoleName;
    }

    private function syncNone(ExportType $exportType): void
    {
        $exportType->role_name = null;
    }

    private function giveAppPermissionsToRole(Role $role, string $exportPermissionName): void
    {
        if (! $role->hasPermissionTo($exportPermissionName)) {
            $role->givePermissionTo($exportPermissionName);
        }

        if (! $role->hasPermissionTo('see-app-bue-exports')) {
            Permission::findOrCreate('see-app-bue-exports', 'web');
            $role->givePermissionTo('see-app-bue-exports');
        }
    }
}
