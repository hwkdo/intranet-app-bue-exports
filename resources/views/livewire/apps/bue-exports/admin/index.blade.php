<?php

use function Livewire\Volt\{state, title};

title('Bue Exports - Admin');

state(['activeTab' => 'export-typen']);

?>

<div>
<x-intranet-app-bue-exports::bue-exports-layout heading="Bue Exports" subheading="Admin">
    <flux:tab.group>
        <flux:tabs wire:model="activeTab">
            <flux:tab name="export-typen" icon="arrow-down-tray">Export-Typen</flux:tab>
            <flux:tab name="hintergrundbild" icon="photo">Hintergrundbild</flux:tab>
            <flux:tab name="einstellungen" icon="cog-6-tooth">Einstellungen</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="export-typen">
            <div style="min-height: 400px;">
                <livewire:apps.bue-exports.admin.export-types />
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="hintergrundbild">
            <div style="min-height: 400px;">
                @livewire('intranet-app-base::app-background-image', [
                    'appIdentifier' => 'bue-exports',
                ])
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="einstellungen">
            <div style="min-height: 400px;">
                @livewire('intranet-app-base::admin-settings', [
                    'appIdentifier' => 'bue-exports',
                    'settingsModelClass' => '\Hwkdo\IntranetAppBueExports\Models\IntranetAppBueExportsSettings',
                    'appSettingsClass' => '\Hwkdo\IntranetAppBueExports\Data\AppSettings',
                ])
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</x-intranet-app-bue-exports::bue-exports-layout>
</div>
