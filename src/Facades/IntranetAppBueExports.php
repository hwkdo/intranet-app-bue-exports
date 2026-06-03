<?php

namespace Hwkdo\IntranetAppBueExports\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Hwkdo\IntranetAppBueExports\IntranetAppBueExports
 */
class IntranetAppBueExports extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hwkdo\IntranetAppBueExports\IntranetAppBueExports::class;
    }
}
