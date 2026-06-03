@props([
    'heading' => '',
    'subheading' => '',
    'navItems' => []
])

@php
    $defaultNavItems = [
        ['label' => 'Übersicht', 'href' => route('apps.bue-exports.index'), 'icon' => 'home', 'description' => 'Zurück zur Übersicht', 'buttonText' => 'Übersicht anzeigen'],
        ['label' => 'Beispielseite', 'href' => route('apps.bue-exports.example'), 'icon' => 'document-text', 'description' => 'Beispielseite anzeigen', 'buttonText' => 'Beispielseite öffnen'],
        ['label' => 'Meine Einstellungen', 'href' => route('apps.bue-exports.settings.user'), 'icon' => 'cog-6-tooth', 'description' => 'Persönliche Einstellungen anpassen', 'buttonText' => 'Einstellungen öffnen'],
        ['label' => 'App-Info', 'href' => route('apps.bue-exports.info'), 'icon' => 'information-circle', 'description' => 'Installierte Version und Release-Historie', 'buttonText' => 'App-Info anzeigen'],
        ['label' => 'Admin', 'href' => route('apps.bue-exports.admin.index'), 'icon' => 'shield-check', 'description' => 'Administrationsbereich verwalten', 'buttonText' => 'Admin öffnen', 'permission' => 'manage-app-bue-exports']
    ];
    
    $navItems = !empty($navItems) ? $navItems : $defaultNavItems;
    $customBgUrl = \Hwkdo\IntranetAppBase\Models\AppBackground::getCustomBackgroundUrl('bue-exports');
@endphp

@if($customBgUrl)
    @push('app-styles')
    <style data-app-bg data-ts="{{ uniqid() }}">
        :root { --app-bg-image: url('{{ $customBgUrl }}'); }
    </style>
    @endpush
@endif

@if(request()->routeIs('apps.bue-exports.index'))
    <x-intranet-app-base::app-layout 
        app-identifier="bue-exports"
        :heading="$heading"
        :subheading="$subheading"
        :nav-items="$navItems"
        :wrap-in-card="false"
    >
        <x-intranet-app-base::app-index-auto 
            app-identifier="bue-exports"
            app-name="BueExports App"
            app-description="Generated app: Bue Exports"
            :nav-items="$navItems"
            welcome-title="Willkommen zur BueExports App"
            welcome-description="Dies ist eine Beispiel-App, die als BueExports für neue Intranet-Apps dient."
        />
    </x-intranet-app-base::app-layout>
@else
    <x-intranet-app-base::app-layout 
        app-identifier="bue-exports"
        :heading="$heading"
        :subheading="$subheading"
        :nav-items="$navItems"
        :wrap-in-card="true"
    >
        {{ $slot }}
    </x-intranet-app-base::app-layout>
@endif
