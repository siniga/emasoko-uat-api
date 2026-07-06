<?php

namespace App\Services\Reports;

use App\Exports\CollectionReportQueryExport;
use App\Exports\StructuredReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportService
{
    public function downloadXls(array $headings, array $rows, string $filename)
    {
        return Excel::download(new StructuredReportExport($headings, $rows), $filename);
    }

    public function downloadCollectionXls(Builder $query, bool $actual, string $filename)
    {
        return Excel::download(new CollectionReportQueryExport($query, $actual), $filename);
    }

    public function downloadPdf(string $title, array $headings, array $rows, string $filename)
    {
        $pdf = Pdf::loadView('reports.pdf.report', [
            'title' => $title,
            'headings' => $headings,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
