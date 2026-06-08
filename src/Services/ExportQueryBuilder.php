<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Services;

use Hwkdo\BueLaravel\BueLaravel;
use Hwkdo\IntranetAppBueExports\Data\CustomFilterDefinition;
use Hwkdo\IntranetAppBueExports\Data\ExportFilterInput;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\ValidationException;

class ExportQueryBuilder
{
    public function __construct(
        private readonly BueLaravel $bueLaravel,
    ) {}

    public function build(ExportType $type, ExportFilterInput $filters): Builder
    {
        $connectionName = config('intranet-app-bue-exports.bue_connection.name');

        $query = $this->bueLaravel
            ->using($connectionName)
            ->table($type->oracle_view)
            ->select('*');

        if ($filters->nurMitEmail && filled($type->email_field)) {
            $query->whereNotNull($type->email_field);
        }

        if (filled($type->gewerke_field) && $filters->gewerke !== []) {
            $query->whereIn($type->gewerke_field, $filters->gewerke);
        }

        if (filled($type->orte_field) && $filters->orte !== []) {
            $query->whereIn($type->orte_field, $filters->orte);
        }

        if (filled($type->landkreise_field) && $filters->landkreise !== []) {
            $query->whereIn($type->landkreise_field, $filters->landkreise);
        }

        foreach ($type->customFilterDefinitions() as $definition) {
            $this->applyCustomFilter($query, $definition, $filters->custom[$definition->key] ?? null);
        }

        $limit = min($filters->maxRecords ?? $type->max_records, $type->max_records);

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query;
    }

    private function applyCustomFilter(Builder $query, CustomFilterDefinition $definition, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! in_array($definition->operator, CustomFilterDefinition::allowedOperators(), true)) {
            throw ValidationException::withMessages([
                'custom.'.$definition->key => 'Ungültiger Operator.',
            ]);
        }

        if ($definition->type === 'number' && ! is_numeric($value)) {
            throw ValidationException::withMessages([
                'custom.'.$definition->key => 'Bitte geben Sie eine Zahl ein.',
            ]);
        }

        $query->where($definition->field, $definition->operator, $value);
    }
}
