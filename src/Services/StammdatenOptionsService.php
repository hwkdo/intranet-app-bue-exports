<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Services;

use Hwkdo\BueLaravel\BueLaravel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StammdatenOptionsService
{
    public function __construct(
        private readonly BueLaravel $bueLaravel,
    ) {}

    /**
     * @return Collection<int, object>
     */
    public function gewerke(): Collection
    {
        return $this->loadOptions('gewerke');
    }

    /**
     * @return Collection<int, object>
     */
    public function orte(): Collection
    {
        return $this->loadOptions('orte');
    }

    /**
     * @return Collection<int, object>
     */
    public function landkreise(): Collection
    {
        return $this->loadOptions('landkreise');
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    public function anlagen(): Collection
    {
        return $this->loadLabeledOptions('anlagen');
    }

    /**
     * @return Collection<int, object>
     */
    private function loadOptions(string $key): Collection
    {
        $view = config("intranet-app-bue-exports.stamm_views.{$key}");
        $column = config('intranet-app-bue-exports.stamm_value_column', 'name');
        $connectionName = config('intranet-app-bue-exports.bue_connection.name');
        $ttl = (int) config('intranet-app-bue-exports.stamm_cache_ttl', 3600);

        return Cache::remember(
            "intranet-app-bue-exports.stamm.v2.{$key}",
            $ttl,
            fn (): Collection => $this->bueLaravel
                ->using($connectionName)
                ->table($view)
                ->select($column)
                ->distinct()
                ->orderBy($column)
                ->get(),
        );
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function loadLabeledOptions(string $key): Collection
    {
        /** @var array{view: string, value_column: string, label_column: string}|null $config */
        $config = config("intranet-app-bue-exports.labeled_stamm_views.{$key}");

        if ($config === null) {
            return collect();
        }

        $connectionName = config('intranet-app-bue-exports.bue_connection.name');
        $ttl = (int) config('intranet-app-bue-exports.stamm_cache_ttl', 3600);

        return Cache::remember(
            "intranet-app-bue-exports.stamm.labeled.v1.{$key}",
            $ttl,
            function () use ($config, $connectionName): Collection {
                $items = $this->bueLaravel
                    ->using($connectionName)
                    ->table($config['view'])
                    ->select($config['value_column'], $config['label_column'])
                    ->orderBy($config['label_column'])
                    ->get();

                if ($items->isEmpty()) {
                    return collect();
                }

                $valueKey = $this->resolveResultColumnKey($config['value_column'], $items);
                $labelKey = $this->resolveResultColumnKey($config['label_column'], $items);

                return $items
                    ->map(fn (object $row): array => [
                        'value' => (string) data_get($row, $valueKey),
                        'label' => (string) data_get($row, $labelKey),
                    ])
                    ->filter(fn (array $option): bool => $option['value'] !== '' && $option['label'] !== '')
                    ->values();
            },
        );
    }

    /**
     * @return list<string>
     */
    public function valuesFor(string $key): array
    {
        $items = $this->loadOptions($key);

        if ($items->isEmpty()) {
            return [];
        }

        $columnKey = $this->resolveResultColumnKey(
            config('intranet-app-bue-exports.stamm_value_column', 'name'),
            $items,
        );

        return $items
            ->pluck($columnKey)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Oracle liefert Spaltennamen in Kleinbuchstaben, unabhängig von SELECT-Großschreibung.
     */
    private function resolveResultColumnKey(string $configuredColumn, Collection $items): string
    {
        $keys = array_keys((array) $items->first());
        $normalized = strtolower($configuredColumn);

        foreach ($keys as $key) {
            if (strtolower((string) $key) === $normalized) {
                return (string) $key;
            }
        }

        return (string) ($keys[0] ?? $normalized);
    }
}
