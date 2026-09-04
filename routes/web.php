<?php

declare(strict_types=1);

use Hwkdo\IntranetAppBueExports\Http\Controllers\ExportDownloadController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['web', 'auth', 'can:see-app-bue-exports'])->group(function (): void {
    Volt::route('apps/bue-exports', 'apps.bue-exports.index')->name('apps.bue-exports.index');

    Volt::route('apps/bue-exports/export', 'apps.bue-exports.export.index')->name('apps.bue-exports.export');
    Route::get('apps/bue-exports/export/{exportType}/download', ExportDownloadController::class)
        ->name('apps.bue-exports.export.download');
    Volt::route('apps/bue-exports/info', 'apps.bue-exports.info')->name('apps.bue-exports.info');
});

Route::middleware(['web', 'auth', 'can:manage-app-bue-exports'])->group(function (): void {
    Volt::route('apps/bue-exports/admin', 'apps.bue-exports.admin.index')->name('apps.bue-exports.admin.index');

    Volt::route('apps/bue-exports/admin/export-types', 'apps.bue-exports.admin.export-types')->name('apps.bue-exports.admin.export-types');
});
