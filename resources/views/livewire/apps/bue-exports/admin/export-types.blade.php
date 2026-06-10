<?php

use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Hwkdo\IntranetAppBueExports\Data\ExportTypeAccessInput;
use Hwkdo\IntranetAppBueExports\Enums\AccessModeEnum;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Hwkdo\IntranetAppBueExports\Services\ExportTypeAccessService;
use Hwkdo\IntranetAppBueExports\Support\ExportTypeValidator;

use function Livewire\Volt\computed;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;
use function Livewire\Volt\updated;

state([
    'editingId' => null,
    'name' => '',
    'slug' => '',
    'oracle_view' => '',
    'email_field' => null,
    'gewerke_field' => null,
    'orte_field' => null,
    'landkreise_field' => null,
    'anlage_field' => null,
    'is_active' => true,
    'sort_order' => 0,
    'max_records' => 10_000,
    'accessMode' => 'none',
    'selectedUserIds' => [],
    'existingRoleName' => null,
    'customFilters' => [],
    'excludedColumnsInput' => '',
    'showForm' => false,
    'originalSlug' => null,
]);

rules([
    'name' => 'required|string|max:255',
    'slug' => 'required|string|max:255|regex:/^[a-z0-9_]+$/',
    'oracle_view' => 'required|string|regex:/^[a-zA-Z0-9_.]+$/',
    'email_field' => 'nullable|string|regex:/^[A-Z0-9_]+$/',
    'gewerke_field' => 'nullable|string|regex:/^[A-Z0-9_]+$/',
    'orte_field' => 'nullable|string|regex:/^[A-Z0-9_]+$/',
    'landkreise_field' => 'nullable|string|regex:/^[A-Z0-9_]+$/',
    'anlage_field' => 'nullable|string|regex:/^[A-Z0-9_]+$/',
    'max_records' => 'required|integer|min:1|max:1000000',
]);

$exportTypes = computed(fn () => ExportType::query()->orderBy('sort_order')->orderBy('name')->get());

$activeUsers = computed(fn () => User::aktiv()->orderBy('nachname')->orderBy('vorname')->get());

$availableRoles = computed(fn () => Role::query()->orderBy('name')->get());

$permissionPreview = computed(function (): string {
    if ($this->slug === '') {
        return 'intranet-app-bue-exports-…';
    }

    return ExportType::permissionNamePreview($this->slug);
});

$rolePreview = computed(function (): string {
    if ($this->slug === '') {
        return 'Intranet-App-BuE-Exports-…';
    }

    return ExportType::roleNamePreview($this->slug);
});

updated([
    'name' => function (string $value): void {
        if ($this->editingId === null) {
            $this->slug = ExportType::slugFromName($value);
        }
    },
]);

$resetForm = function (): void {
    $this->editingId = null;
    $this->originalSlug = null;
    $this->name = '';
    $this->slug = '';
    $this->oracle_view = '';
    $this->email_field = null;
    $this->gewerke_field = null;
    $this->orte_field = null;
    $this->landkreise_field = null;
    $this->anlage_field = null;
    $this->is_active = true;
    $this->sort_order = 0;
    $this->max_records = (int) config('intranet-app-bue-exports.default_max_records', 10_000);
    $this->accessMode = AccessModeEnum::None->value;
    $this->selectedUserIds = [];
    $this->existingRoleName = null;
    $this->customFilters = [];
    $this->excludedColumnsInput = '';
    $this->showForm = false;
    $this->resetValidation();
};

$create = function (): void {
    $this->resetForm();
    $this->showForm = true;
};

$edit = function (int $id): void {
    $type = ExportType::findOrFail($id);
    $this->editingId = $id;
    $this->originalSlug = $type->slug;
    $this->name = $type->name;
    $this->slug = $type->slug;
    $this->oracle_view = $type->oracle_view;
    $this->email_field = $type->email_field;
    $this->gewerke_field = $type->gewerke_field;
    $this->orte_field = $type->orte_field;
    $this->landkreise_field = $type->landkreise_field;
    $this->anlage_field = $type->anlage_field;
    $this->is_active = $type->is_active;
    $this->sort_order = $type->sort_order;
    $this->max_records = $type->max_records;
    $this->accessMode = $type->access_mode->value;
    $this->customFilters = $type->custom_filters ?? [];
    $this->excludedColumnsInput = implode(', ', $type->excludedColumns());

    if ($type->access_mode === AccessModeEnum::NewGroup && $type->role_name) {
        $role = Role::findByName($type->role_name, 'web');
        $this->selectedUserIds = $role->users->pluck('id')->all();
    }

    if ($type->access_mode === AccessModeEnum::ExistingRole) {
        $this->existingRoleName = $type->role_name;
    }

    $this->showForm = true;
};

$addCustomFilter = function (): void {
    $this->customFilters[] = [
        'key' => '',
        'label' => '',
        'field' => '',
        'operator' => '>',
        'type' => 'number',
    ];
};

$removeCustomFilter = function (int $index): void {
    unset($this->customFilters[$index]);
    $this->customFilters = array_values($this->customFilters);
};

$save = function (): void {
    $this->validate();

    $customFilters = ExportTypeValidator::validateCustomFilters($this->customFilters);
    $excludedColumns = ExportTypeValidator::validateExcludedColumns($this->excludedColumnsInput);

    $attributes = [
        'name' => $this->name,
        'slug' => $this->slug,
        'oracle_view' => $this->oracle_view,
        'email_field' => $this->email_field ?: null,
        'gewerke_field' => $this->gewerke_field ?: null,
        'orte_field' => $this->orte_field ?: null,
        'landkreise_field' => $this->landkreise_field ?: null,
        'anlage_field' => $this->anlage_field ?: null,
        'custom_filters' => $customFilters,
        'excluded_columns' => $excludedColumns,
        'is_active' => $this->is_active,
        'sort_order' => $this->sort_order,
        'max_records' => (int) $this->max_records,
    ];

    $accessService = app(ExportTypeAccessService::class);
    $attributes['permission_name'] = $accessService->permissionNameFromSlug($this->slug);
    $attributes['access_mode'] = AccessModeEnum::from($this->accessMode);

    if ($this->editingId) {
        $type = ExportType::findOrFail($this->editingId);
        $type->update($attributes);
    } else {
        $type = ExportType::create($attributes);
    }

    $accessService->syncAccess(new ExportTypeAccessInput(
        exportType: $type->fresh(),
        accessMode: AccessModeEnum::from($this->accessMode),
        selectedUserIds: $this->selectedUserIds,
        existingRoleName: $this->existingRoleName,
        oldSlug: $this->originalSlug,
    ));

    Flux::toast(
        $this->editingId ? 'Export-Typ wurde aktualisiert.' : 'Export-Typ wurde erstellt.',
        variant: 'success',
    );

    $this->resetForm();
    unset($this->exportTypes);
};

$delete = function (int $id): void {
    ExportType::findOrFail($id)->delete();
    Flux::toast('Export-Typ wurde gelöscht.', variant: 'success');
    unset($this->exportTypes);
};

$cancel = function (): void {
    $this->resetForm();
};

$memberCount = function (?string $roleName): int {
    if ($roleName === null) {
        return 0;
    }

    try {
        return Role::findByName($roleName, 'web')->users()->count();
    } catch (Throwable) {
        return 0;
    }
};

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">Export-Typen</flux:heading>
        @if (! $showForm)
            <flux:button wire:click="create" variant="primary" icon="plus">Neuer Export-Typ</flux:button>
        @endif
    </div>

    @if ($showForm)
        <flux:card>
            <flux:heading size="md" class="mb-4">{{ $editingId ? 'Export-Typ bearbeiten' : 'Export-Typ anlegen' }}</flux:heading>

            <form wire:submit="save" class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model.live="name" label="Name" required />
                    <flux:input wire:model="slug" label="Slug" required />
                    <flux:input wire:model="oracle_view" label="Oracle-View" class="md:col-span-2" required />
                    <flux:input wire:model="email_field" label="E-Mail-Feld" />
                    <flux:input wire:model="gewerke_field" label="Gewerke-Feld" />
                    <flux:input wire:model="orte_field" label="Orte-Feld" />
                    <flux:input wire:model="landkreise_field" label="Landkreise-Feld" />
                    <flux:input wire:model="anlage_field" label="Anlage-Feld" />
                    <flux:input wire:model="sort_order" type="number" label="Sortierung" />
                    <flux:input wire:model="max_records" type="number" min="1" label="Maximale Anzahl Datensätze" description="Begrenzt die Zeilenanzahl pro Excel-Export." required />
                    <flux:switch wire:model="is_active" label="Aktiv" />
                    <flux:input
                        wire:model="excludedColumnsInput"
                        label="Ausgeschlossene Spalten"
                        description="Kommagetrennte Oracle-Spaltennamen, die nicht im Excel-Export erscheinen (z. B. BEITRAG, INTERN_ID)."
                        placeholder="BEITRAG"
                        class="md:col-span-2"
                    />
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">Export-spezifische Filter</flux:heading>
                        <flux:button type="button" wire:click="addCustomFilter" size="sm" icon="plus">Filter hinzufügen</flux:button>
                    </div>

                    @foreach ($customFilters as $index => $filter)
                        <div class="grid gap-3 rounded-lg border p-4 md:grid-cols-5" wire:key="filter-{{ $index }}">
                            <flux:input wire:model="customFilters.{{ $index }}.key" label="Schlüssel" />
                            <flux:input wire:model="customFilters.{{ $index }}.label" label="Label" />
                            <flux:input wire:model="customFilters.{{ $index }}.field" label="Feld" />
                            <flux:select wire:model="customFilters.{{ $index }}.operator" label="Operator">
                                @foreach (['>', '<', '>=', '<=', '=', 'like'] as $operator)
                                    <flux:select.option value="{{ $operator }}">{{ $operator }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <div class="flex items-end gap-2">
                                <flux:select wire:model="customFilters.{{ $index }}.type" label="Typ" class="flex-1">
                                    <flux:select.option value="number">Zahl</flux:select.option>
                                    <flux:select.option value="text">Text</flux:select.option>
                                </flux:select>
                                <flux:button type="button" wire:click="removeCustomFilter({{ $index }})" icon="trash" variant="danger" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-4 rounded-lg border p-4">
                    <flux:heading size="sm">Zugriff</flux:heading>

                    <flux:text size="sm">
                        Permission (automatisch): <code class="font-mono">{{ $this->permissionPreview }}</code>
                    </flux:text>

                    <flux:radio.group wire:model.live="accessMode" label="Wie soll der Zugriff vergeben werden?">
                        @foreach (AccessModeEnum::cases() as $mode)
                            <flux:radio value="{{ $mode->value }}" label="{{ $mode->label() }}" />
                        @endforeach
                    </flux:radio.group>

                    @if ($accessMode === 'new_group')
                        <flux:text size="sm">
                            Rollenname (automatisch): <code class="font-mono">{{ $this->rolePreview }}</code>
                        </flux:text>
                        <flux:select
                            wire:model="selectedUserIds"
                            variant="listbox"
                            multiple
                            searchable
                            label="Mitarbeiter mit Zugriff"
                            indicator="checkbox"
                            selected-suffix="Mitarbeiter ausgewählt"
                        >
                            @foreach ($this->activeUsers as $user)
                                <flux:select.option value="{{ $user->id }}">{{ $user->nachname }}, {{ $user->vorname }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif

                    @if ($accessMode === 'existing_role')
                        <flux:select wire:model="existingRoleName" variant="listbox" searchable label="Bestehende Rolle">
                            <flux:select.option value="">— Rolle wählen —</flux:select.option>
                            @foreach ($this->availableRoles as $role)
                                <flux:select.option value="{{ $role->name }}">{{ $role->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:callout variant="info" icon="information-circle">
                            Die Export-Permission wird der gewählten Rolle hinzugefügt. Bestehende Mitglieder dieser Rolle erhalten damit automatisch Zugriff.
                        </flux:callout>
                    @endif

                    @if ($accessMode === 'none')
                        <flux:callout variant="info" icon="information-circle">
                            Die Permission wird angelegt, aber nicht automatisch verteilt. Bitte weisen Sie sie manuell unter
                            @can('manage-users')
                                <a href="{{ route('manager.users-roles.roles') }}" class="underline" wire:navigate>Benutzer &amp; Rollen</a>
                            @else
                                Benutzer &amp; Rollen
                            @endcan
                            zu.
                        </flux:callout>
                    @endif
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" wire:click="cancel">Abbrechen</flux:button>
                    <flux:button type="submit" variant="primary">Speichern</flux:button>
                </div>
            </form>
        </flux:card>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Permission</flux:table.column>
                <flux:table.column>Max. Datensätze</flux:table.column>
                <flux:table.column>Zugriff</flux:table.column>
                <flux:table.column>Aktiv</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->exportTypes as $type)
                    <flux:table.row wire:key="type-{{ $type->id }}">
                        <flux:table.cell>{{ $type->name }}</flux:table.cell>
                        <flux:table.cell><code>{{ $type->slug }}</code></flux:table.cell>
                        <flux:table.cell><code class="text-xs">{{ $type->permission_name }}</code></flux:table.cell>
                        <flux:table.cell>{{ number_format($type->max_records, 0, ',', '.') }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $type->access_mode->label() }}
                            @if ($type->role_name)
                                <br><span class="text-xs text-zinc-500">{{ $type->role_name }} ({{ $this->memberCount($type->role_name) }})</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="{{ $type->is_active ? 'green' : 'zinc' }}">{{ $type->is_active ? 'Ja' : 'Nein' }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button wire:click="edit({{ $type->id }})" size="sm">Bearbeiten</flux:button>
                                <flux:button wire:click="delete({{ $type->id }})" wire:confirm="Export-Typ wirklich löschen?" size="sm" variant="danger">Löschen</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">Noch keine Export-Typen vorhanden.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    @endif
</div>
