<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Asset;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Generates a printable PDF listing every asset currently assigned to an
 * employee. Used by HR for handover sheets and by ITAM for audits.
 */
class GenerateEmployeeAssetSummaryPdf
{
    public function __invoke(Employee $employee): DomPdf
    {
        $employee->loadMissing(['branch', 'department']);

        $assets = $employee->organization
            ->id
            ? Asset::query()
                ->where('organization_id', $employee->organization_id)
                ->where('assigned_employee_id', $employee->id)
                ->with(['category', 'assetModel', 'supplier', 'branch'])
                ->orderBy('code')
                ->get()
            : collect();

        return Pdf::loadView('pdf.employee-asset-summary', [
            'employee' => $employee,
            'assets' => $assets,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');
    }
}
