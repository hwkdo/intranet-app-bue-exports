<?php

use function Livewire\Volt\{title};

title('BueExports - App-Info');

?>

<x-intranet-app-bue-exports::bue-exports-layout heading="App-Info" subheading="Installierte Version und Release-Historie">
    @livewire('intranet-app-base::app-info', ['appIdentifier' => 'bue-exports'])
</x-intranet-app-bue-exports::bue-exports-layout>
