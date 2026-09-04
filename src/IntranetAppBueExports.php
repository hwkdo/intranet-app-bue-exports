<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports;

use Hwkdo\IntranetAppBase\Data\SearchActionDefinition;
use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Hwkdo\IntranetAppBase\Interfaces\ProvidesSearchActionsInterface;
use Hwkdo\IntranetAppBueExports\Data\AppSettings;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IntranetAppBueExports implements IntranetAppInterface, ProvidesSearchActionsInterface
{
    public static function app_name(): string
    {
        return 'BueExports';
    }

    public static function app_icon(): string
    {
        return 'magnifying-glass';
    }

    public static function identifier(): string
    {
        return 'bue-exports';
    }

    public static function roles_admin(): Collection
    {
        return collect(config('intranet-app-bue-exports.roles.admin'));
    }

    public static function roles_user(): Collection
    {
        return collect(config('intranet-app-bue-exports.roles.user'));
    }

    public static function userSettingsClass(): ?string
    {
        return null;
    }

    public static function appSettingsClass(): ?string
    {
        return AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }

    /**
     * @return list<SearchActionDefinition>
     */
    public static function searchActions(): array
    {
        return ExportType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ExportType $type): SearchActionDefinition => self::searchActionFor($type))
            ->values()
            ->all();
    }

    private static function searchActionFor(ExportType $type): SearchActionDefinition
    {
        $requiresForm = $type->requiresExportForm();

        return new SearchActionDefinition(
            key: 'bue-exports.export.'.$type->slug,
            title: 'Export: '.$type->name,
            keywords: self::keywordsFor($type),
            routeName: $requiresForm
                ? 'apps.bue-exports.export'
                : 'apps.bue-exports.export.download',
            appIdentifier: self::identifier(),
            appName: self::app_name(),
            icon: $requiresForm ? self::app_icon() : 'arrow-down-tray',
            permission: null,
            subtitle: self::app_name(),
            sort: 100 + (int) $type->sort_order,
            download: ! $requiresForm,
            routeParameters: $requiresForm
                ? []
                : ['exportType' => $type->slug],
            queryParameters: $requiresForm
                ? ['type' => $type->slug]
                : [],
            anyOfPermissions: [
                $type->permission_name,
                'manage-app-bue-exports',
            ],
        );
    }

    /**
     * @return list<string>
     */
    private static function keywordsFor(ExportType $type): array
    {
        $name = trim($type->name);
        $slugAsWords = str_replace('_', ' ', $type->slug);

        return array_values(array_unique(array_filter([
            mb_strtolower($name),
            mb_strtolower($slugAsWords),
            'export '.mb_strtolower($name),
            mb_strtolower($name).' export',
            'excel',
            'bue',
            'bue export',
            Str::lower($type->slug),
        ])));
    }
}
