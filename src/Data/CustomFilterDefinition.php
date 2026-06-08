<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Data;

use Spatie\LaravelData\Data;

class CustomFilterDefinition extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public string $field,
        public string $operator,
        public string $type,
    ) {}

    /**
     * @return list<string>
     */
    public static function allowedOperators(): array
    {
        return ['>', '<', '>=', '<=', '=', 'like'];
    }

    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return ['number', 'text'];
    }
}
