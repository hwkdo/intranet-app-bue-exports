<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Enums;

enum AccessModeEnum: string
{
    case None = 'none';
    case NewGroup = 'new_group';
    case ExistingRole = 'existing_role';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Manuell im Manager zuweisen',
            self::NewGroup => 'Neue Gruppe erstellen',
            self::ExistingRole => 'Bestehende Rolle verwenden',
        };
    }
}
