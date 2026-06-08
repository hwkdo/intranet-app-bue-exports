<?php

namespace Hwkdo\IntranetAppBueExports\Models;

use Hwkdo\IntranetAppBueExports\Data\AppSettings;
use Illuminate\Database\Eloquent\Model;

class IntranetAppBueExportsSettings extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => AppSettings::class.':default',
        ];
    }

    public static function current(): ?IntranetAppBueExportsSettings
    {
        return self::orderBy('version', 'desc')->first();
    }
}
