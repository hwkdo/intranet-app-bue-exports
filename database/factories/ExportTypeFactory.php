<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Database\Factories;

use Hwkdo\IntranetAppBueExports\Enums\AccessModeEnum;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Hwkdo\IntranetAppBueExports\Services\ExportTypeAccessService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportType>
 */
class ExportTypeFactory extends Factory
{
    protected $model = ExportType::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug('_');
        $accessService = app(ExportTypeAccessService::class);

        return [
            'name' => fake()->words(2, true),
            'slug' => $slug,
            'oracle_view' => 'hwkuserro.test_view',
            'email_field' => 'EMAIL',
            'gewerke_field' => 'GEWERBE',
            'orte_field' => 'ORT',
            'landkreise_field' => 'LANDKREIS',
            'custom_filters' => [],
            'is_active' => true,
            'sort_order' => 0,
            'max_records' => 10_000,
            'permission_name' => $accessService->permissionNameFromSlug($slug),
            'access_mode' => AccessModeEnum::None,
            'role_name' => null,
        ];
    }
}
