<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Exports;

use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Hwkdo\IntranetAppBueExports\Services\ExportColumnResolver;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BueQueryExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var list<string> */
    private array $columnKeys = [];

    /** @var list<string> */
    private array $headings = [];

    public function __construct(
        private readonly Builder $query,
        ExportType $exportType,
        ?ExportColumnResolver $columnResolver = null,
    ) {
        $resolved = ($columnResolver ?? app(ExportColumnResolver::class))->resolve($exportType, $this->query);

        $this->columnKeys = $resolved['keys'];
        $this->headings = $resolved['headings'];
    }

    public function collection(): Collection
    {
        return $this->query->get();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @param  object  $row
     * @return list<mixed>
     */
    public function map($row): array
    {
        return array_map(
            fn (string $key): mixed => data_get($row, $key),
            $this->columnKeys,
        );
    }
}
