<?php

use function Livewire\Volt\{title};

title('BueExports - Meine Einstellungen');

?>

<x-intranet-app-bue-exports::bue-exports-layout heading="Meine Einstellungen" subheading="Persönliche Einstellungen für die BueExports App">
    @livewire('intranet-app-base::user-settings', ['appIdentifier' => 'bue-exports'])
</x-intranet-app-bue-exports::bue-exports-layout>
