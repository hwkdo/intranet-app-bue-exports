<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Services;

use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Illuminate\Database\Query\Builder;

class ExportColumnResolver
{
    /**
     * @return array{keys: list<string>, headings: list<string>}
     */
    public function resolve(ExportType $type, Builder $query): array
    {
        $keys = $this->discoverColumnKeys($query, $type);
        $excluded = $this->normalizedExcludedColumns($type);

        $exportKeys = [];
        $headings = [];

        foreach ($keys as $key) {
            if (in_array(strtolower($key), $excluded, true)) {
                continue;
            }

            $exportKeys[] = $key;
            $headings[] = $this->labelForColumn($type, $key);
        }

        return [
            'keys' => $exportKeys,
            'headings' => $headings,
        ];
    }

    /**
     * @return list<string>
     */
    private function discoverColumnKeys(Builder $query, ExportType $type): array
    {
        $sample = (clone $query)->limit(1)->get();

        if ($sample->isNotEmpty()) {
            return array_keys((array) $sample->first());
        }

        return array_values(array_unique(array_map(
            fn (string $labelKey): string => $labelKey,
            array_keys($type->columnLabels()),
        )));
    }

    /**
     * @return list<string>
     */
    private function normalizedExcludedColumns(ExportType $type): array
    {
        return array_map(
            strtolower(...),
            $type->excludedColumns(),
        );
    }

    private function labelForColumn(ExportType $type, string $key): string
    {
        $labels = $type->columnLabels();
        $normalizedKey = strtolower($key);

        foreach ($labels as $labelKey => $label) {
            if (strtolower((string) $labelKey) === $normalizedKey) {
                return $label;
            }
        }

        return str_replace('_', ' ', ucwords(strtolower($key)));
    }
}
