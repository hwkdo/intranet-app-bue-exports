<?php

use Flux\Flux;
use Hwkdo\IntranetAppBueExports\Data\ExportFilterInput;
use Hwkdo\IntranetAppBueExports\Exports\BueQueryExport;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Hwkdo\IntranetAppBueExports\Services\ExportQueryBuilder;
use Hwkdo\IntranetAppBueExports\Services\StammdatenOptionsService;
use Maatwebsite\Excel\Facades\Excel;

use function Livewire\Volt\{computed, state, title, updated};

title('Bue Exports - Exporte');

state([
    'exportTypeId' => null,
    'maxRecords' => null,
    'nurMitEmail' => false,
    'gewerke' => [],
    'orte' => [],
    'landkreise' => [],
    'custom' => [],
]);

$exportTypes = computed(function () {
    return ExportType::query()
        ->where('is_active', true)
        ->accessibleBy()
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();
});

$selectedType = computed(function () {
    if ($this->exportTypeId === null) {
        return null;
    }

    return $this->exportTypes->firstWhere('id', (int) $this->exportTypeId);
});

$gewerkeOptions = computed(fn () => app(StammdatenOptionsService::class)->valuesFor('gewerke'));

$orteOptions = computed(fn () => app(StammdatenOptionsService::class)->valuesFor('orte'));

$landkreiseOptions = computed(fn () => app(StammdatenOptionsService::class)->valuesFor('landkreise'));

updated([
    'exportTypeId' => function (): void {
        $this->nurMitEmail = false;
        $this->gewerke = [];
        $this->orte = [];
        $this->landkreise = [];
        $this->custom = [];
        $this->maxRecords = $this->selectedType?->max_records;
    },
]);

$exportExcel = function () {
    $type = $this->selectedType;

    if ($type === null) {
        Flux::toast('Bitte wählen Sie einen Export-Typ.', variant: 'danger');

        return;
    }

    $this->authorize($type->permission_name);

    $this->validate([
        'maxRecords' => ['required', 'integer', 'min:1', 'max:'.$type->max_records],
    ], [
        'maxRecords.required' => 'Bitte geben Sie die maximale Anzahl Datensätze an.',
        'maxRecords.integer' => 'Die maximale Anzahl muss eine ganze Zahl sein.',
        'maxRecords.min' => 'Mindestens 1 Datensatz.',
        'maxRecords.max' => 'Maximal '.$type->max_records.' Datensätze erlaubt.',
    ]);

    $filters = new ExportFilterInput(
        nurMitEmail: $this->nurMitEmail,
        gewerke: $this->gewerke,
        orte: $this->orte,
        landkreise: $this->landkreise,
        custom: $this->custom,
        maxRecords: (int) $this->maxRecords,
    );

    $query = app(ExportQueryBuilder::class)->build($type, $filters);
    $filename = $type->slug.'-'.now()->format('Y-m-d-His').'.xlsx';

    return Excel::download(new BueQueryExport($query, $type), $filename);
};

?>

<div>
<x-intranet-app-bue-exports::bue-exports-layout heading="Exporte" subheading="BUE-Daten als Excel exportieren">
    @if ($this->exportTypes->isEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle">
            Keine Exporte freigegeben — wenden Sie sich an Ihren Administrator.
        </flux:callout>
    @else
        <div class="space-y-6">
            <flux:select wire:model.live="exportTypeId" variant="listbox" label="Export-Typ" placeholder="Export-Typ wählen…">
                @foreach ($this->exportTypes as $type)
                    <flux:select.option value="{{ $type->id }}">{{ $type->name }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->selectedType)
                <flux:card class="space-y-4">
                    <flux:heading size="md">Filter: {{ $this->selectedType->name }}</flux:heading>

                    <flux:input
                        wire:model="maxRecords"
                        type="number"
                        min="1"
                        :max="$this->selectedType->max_records"
                        label="Maximale Anzahl Datensätze"
                        description="Für diesen Export (Obergrenze: {{ number_format($this->selectedType->max_records, 0, ',', '.') }})"
                        required
                    />

                    @if ($this->selectedType->email_field)
                        <flux:checkbox wire:model="nurMitEmail" label="Nur mit E-Mail" />
                    @endif

                    @if ($this->selectedType->gewerke_field)
                        <flux:select wire:model="gewerke" variant="listbox" multiple searchable label="Gewerke" indicator="checkbox">
                            @foreach ($this->gewerkeOptions as $value)
                                <flux:select.option value="{{ $value }}">{{ $value }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif

                    @if ($this->selectedType->orte_field)
                        <flux:select wire:model="orte" variant="listbox" multiple searchable label="Orte" indicator="checkbox">
                            @foreach ($this->orteOptions as $value)
                                <flux:select.option value="{{ $value }}">{{ $value }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif

                    @if ($this->selectedType->landkreise_field)
                        <flux:select wire:model="landkreise" variant="listbox" multiple searchable label="Landkreise" indicator="checkbox">
                            @foreach ($this->landkreiseOptions as $value)
                                <flux:select.option value="{{ $value }}">{{ $value }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif

                    @foreach ($this->selectedType->customFilterDefinitions() as $filter)
                        @if ($filter->type === 'number')
                            <flux:input wire:model="custom.{{ $filter->key }}" type="number" step="any" label="{{ $filter->label }}" />
                        @else
                            <flux:input wire:model="custom.{{ $filter->key }}" label="{{ $filter->label }}" />
                        @endif
                    @endforeach

                    <div class="pt-2">
                        <flux:button wire:click="exportExcel" variant="primary" icon="arrow-down-tray" wire:loading.attr="disabled">
                            Als Excel exportieren
                        </flux:button>
                    </div>
                </flux:card>
            @endif
        </div>
    @endif
</x-intranet-app-bue-exports::bue-exports-layout>
</div>
