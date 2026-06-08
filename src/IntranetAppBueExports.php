<?php

namespace Hwkdo\IntranetAppBueExports;

use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Hwkdo\IntranetAppBueExports\Data\AppSettings;
use Hwkdo\IntranetAppBueExports\Data\UserSettings;
use Illuminate\Support\Collection;

class IntranetAppBueExports implements IntranetAppInterface
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
        return UserSettings::class;
    }

    public static function appSettingsClass(): ?string
    {
        return AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }
}
