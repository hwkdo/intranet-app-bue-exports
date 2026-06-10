<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Data;

use Spatie\LaravelData\Data;

class ExportFilterInput extends Data
{
    /**
     * @param  list<string>  $gewerke
     * @param  list<string>  $orte
     * @param  list<string>  $landkreise
     * @param  array<string, mixed>  $custom
     */
    public function __construct(
        public bool $nurMitEmail = false,
        public array $gewerke = [],
        public array $orte = [],
        public array $landkreise = [],
        public ?string $anlage = null,
        public array $custom = [],
        public ?int $maxRecords = null,
    ) {}
}
