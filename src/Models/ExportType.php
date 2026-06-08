<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Models;

use Hwkdo\IntranetAppBueExports\Data\CustomFilterDefinition;
use Hwkdo\IntranetAppBueExports\Database\Factories\ExportTypeFactory;
use Hwkdo\IntranetAppBueExports\Enums\AccessModeEnum;
use Hwkdo\IntranetAppBueExports\Services\ExportTypeAccessService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExportType extends Model
{
    /** @use HasFactory<ExportTypeFactory> */
    use HasFactory;

    protected $table = 'intranet_app_bue_exports_export_types';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'custom_filters' => 'array',
            'excluded_columns' => 'array',
            'column_labels' => 'array',
            'is_active' => 'boolean',
            'access_mode' => AccessModeEnum::class,
            'max_records' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public function excludedColumns(): array
    {
        return $this->excluded_columns ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function columnLabels(): array
    {
        return $this->column_labels ?? [];
    }

    /**
     * @return list<CustomFilterDefinition>
     */
    public function customFilterDefinitions(): array
    {
        return array_map(
            fn (array $filter): CustomFilterDefinition => CustomFilterDefinition::from($filter),
            $this->custom_filters ?? [],
        );
    }

    public static function slugFromName(string $name): string
    {
        return Str::slug($name, '_');
    }

    public function userCanAccess(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if ($user->can('manage-app-bue-exports')) {
            return true;
        }

        return $user->can($this->permission_name);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAccessibleBy($query, ?Authenticatable $user = null)
    {
        $user ??= auth()->user();

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('manage-app-bue-exports')) {
            return $query;
        }

        $permissionNames = static::query()
            ->where('is_active', true)
            ->pluck('permission_name')
            ->filter(fn (string $name): bool => $user->can($name))
            ->values()
            ->all();

        return $query->whereIn('permission_name', $permissionNames);
    }

    public static function permissionNamePreview(string $slug): string
    {
        return app(ExportTypeAccessService::class)->permissionNameFromSlug($slug);
    }

    public static function roleNamePreview(string $slug): string
    {
        return app(ExportTypeAccessService::class)->roleNameFromSlug($slug);
    }

    protected static function newFactory(): ExportTypeFactory
    {
        return ExportTypeFactory::new();
    }
}
