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
