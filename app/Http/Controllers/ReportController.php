<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Partner;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\ReportQueryService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ReportQueryService $reportQueryService,
        protected ReportExportService $reportExportService
    ) {
        $this->middleware('auth');
    }

    public function lists(Request $request)
    {
        $title = 'Reports';
        $filters = $this->reportQueryService->filters($request->all());
        $reportDefinitions = $this->visibleReportDefinitions();

        if (!empty($filters['report_type']) && !array_key_exists($filters['report_type'], $reportDefinitions)) {
            $filters['report_type'] = null;
        }

        $reportPreview = null;

        if (isset($_POST['export_xls']) && !empty($filters['report_type'])) {
            $payload = $this->reportQueryService->exportPayload($filters['report_type'], $filters);
            $title = $reportDefinitions[$filters['report_type']]['label'] ?? 'Report';

            return $this->reportExportService->downloadXls($payload['headings'], $payload['rows'], $payload['filename']);
        }

        if (isset($_POST['export_pdf']) && !empty($filters['report_type'])) {
            $payload = $this->reportQueryService->exportPayload($filters['report_type'], $filters);
            $title = $reportDefinitions[$filters['report_type']]['label'] ?? 'Report';
            $pdfFilename = str_replace('.xlsx', '.pdf', $payload['filename']);

            return $this->reportExportService->downloadPdf($title, $payload['headings'], $payload['rows'], $pdfFilename);
        }

        if (isset($_POST['preview']) && !empty($filters['report_type'])) {
            $reportPreview = $this->reportQueryService->preview($filters['report_type'], $filters);
        }

        $account_types = AccountType::all();
        $accounts = Account::where('active', '=', 1)->get();
        $partners = Partner::filterCondition()->where('approved', '=', 1)->get();
        $mandateStatusOptions = $this->reportQueryService->mandateStatusOptions();
        $collectionStatusOptions = $this->reportQueryService->collectionStatusOptions();

        return view('reports.index', compact(
            'title',
            'account_types',
            'accounts',
            'partners',
            'filters',
            'reportDefinitions',
            'mandateStatusOptions',
            'collectionStatusOptions',
            'reportPreview'
        ));
    }

    protected function visibleReportDefinitions(): array
    {
        return collect($this->reportQueryService->reportDefinitions())
            ->except([
                ReportQueryService::TYPE_CRB_REPORT,
                ReportQueryService::TYPE_ACCOUNTING_REPORT,
                ReportQueryService::TYPE_DELINQUENT_AGE_ANALYSIS,
            ])
            ->all();
    }
}
