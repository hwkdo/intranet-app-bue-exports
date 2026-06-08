<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Data;

use Hwkdo\IntranetAppBueExports\Enums\AccessModeEnum;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Spatie\LaravelData\Data;

class ExportTypeAccessInput extends Data
{
    /**
     * @param  list<int>  $selectedUserIds
     */
    public function __construct(
        public ExportType $exportType,
        public AccessModeEnum $accessMode,
        public array $selectedUserIds = [],
        public ?string $existingRoleName = null,
        public ?string $oldSlug = null,
    ) {}
}
