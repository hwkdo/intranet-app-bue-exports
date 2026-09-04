<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Http\Controllers;

use Hwkdo\IntranetAppBueExports\Data\ExportFilterInput;
use Hwkdo\IntranetAppBueExports\Exports\BueQueryExport;
use Hwkdo\IntranetAppBueExports\Models\ExportType;
use Hwkdo\IntranetAppBueExports\Services\ExportQueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportDownloadController
{
    public function __invoke(
        Request $request,
        ExportType $exportType,
        ExportQueryBuilder $queryBuilder,
    ): BinaryFileResponse|RedirectResponse {
        abort_unless($exportType->is_active, 404);
        abort_unless($exportType->userCanAccess($request->user()), 403);

        if ($exportType->requiresExportForm()) {
            return redirect()->route('apps.bue-exports.export', [
                'type' => $exportType->slug,
            ]);
        }

        $filters = new ExportFilterInput(
            maxRecords: $exportType->max_records,
        );

        $query = $queryBuilder->build($exportType, $filters);
        $filename = $exportType->slug.'-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new BueQueryExport($query, $exportType), $filename);
    }
}
