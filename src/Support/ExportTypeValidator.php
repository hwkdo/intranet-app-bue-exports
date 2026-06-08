<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Support;

use Hwkdo\IntranetAppBueExports\Data\CustomFilterDefinition;
use Illuminate\Validation\ValidationException;

class ExportTypeValidator
{
    public static function oracleViewRule(): string
    {
        return 'required|string|regex:/^[a-zA-Z0-9_.]+$/';
    }

    public static function fieldNameRule(): string
    {
        return 'nullable|string|regex:/^[A-Z0-9_]+$/';
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     * @return list<array<string, mixed>>
     */
    public static function validateCustomFilters(array $filters): array
    {
        $validated = [];

        foreach ($filters as $index => $filter) {
            $key = $filter['key'] ?? null;
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? null;
            $type = $filter['type'] ?? null;

            if (! is_string($key) || ! preg_match('/^[a-z0-9_]+$/', $key)) {
                throw ValidationException::withMessages([
                    "customFilters.{$index}.key" => 'Ungültiger Filter-Schlüssel.',
                ]);
            }

            if (! is_string($field) || ! preg_match('/^[A-Z0-9_]+$/', $field)) {
                throw ValidationException::withMessages([
                    "customFilters.{$index}.field" => 'Ungültiger Feldname.',
                ]);
            }

            if (! in_array($operator, CustomFilterDefinition::allowedOperators(), true)) {
                throw ValidationException::withMessages([
                    "customFilters.{$index}.operator" => 'Ungültiger Operator.',
                ]);
            }

            if (! in_array($type, CustomFilterDefinition::allowedTypes(), true)) {
                throw ValidationException::withMessages([
                    "customFilters.{$index}.type" => 'Ungültiger Typ.',
                ]);
            }

            $validated[] = [
                'key' => $key,
                'label' => (string) ($filter['label'] ?? $key),
                'field' => $field,
                'operator' => $operator,
                'type' => $type,
            ];
        }

        return $validated;
    }

    /**
     * @return list<string>
     */
    public static function validateExcludedColumns(string $input): array
    {
        if (trim($input) === '') {
            return [];
        }

        $columns = array_values(array_unique(array_filter(
            array_map(trim(...), explode(',', $input)),
            fn (string $column): bool => $column !== '',
        )));

        foreach ($columns as $index => $column) {
            if (! preg_match('/^[A-Za-z0-9_]+$/', $column)) {
                throw ValidationException::withMessages([
                    'excludedColumnsInput' => 'Ungültiger Spaltenname an Position '.($index + 1).': '.$column,
                ]);
            }
        }

        return array_map(strtoupper(...), $columns);
    }
}
