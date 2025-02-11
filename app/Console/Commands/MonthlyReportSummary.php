<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonthlyReportSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:monthly-report-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily report summary to Company Approver';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //current month and year
        $month = date('m');
        $year = date('Y');

        //status
        $approvedStatuses = [0, 1, 2];

        //partners
        $partners = Partner::where('status', '=', 202)->where('id', '=', 6)->get();

        Log::debug($partners);

        foreach ($partners as $partner) {
            //query
            $debit_orders = DebitOrder::select('debit_orders.approved', DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'), DB::raw('COUNT(id) as number_of_mandate'))
                ->where(function ($query) {
                    $query->whereIn('debit_orders.approved', [0, 1, 2]);
                })
                ->where('partner_id', '=', $partner->id)
                ->groupBy('debit_orders.approved')->orderBy('debit_orders.approved')->toBase();

            $debit_orders = $debit_orders->whereMonth('created_at', $month)->whereYear('created_at', $year)->get();

            // Map over all approved statuses to ensure complete results
            $mandates = collect($approvedStatuses)->map(function ($status) use ($debit_orders) {
                $order = $debit_orders->firstWhere('approved', $status);
                return [
                    'approved' => $status,
                    'number_of_mandate' => $order ? $order->number_of_mandate : 0,
                    'total_amount' => $order ? $order->total_amount : 0,
                ];
            });

            //total number of mandates
            $total_number_of_mandate = $mandates[0]['number_of_mandate'] + $mandates[1]['number_of_mandate'] + $mandates[2]['number_of_mandate'];

            //summation of mandate values
            $total_mandate_value = $mandates[0]['total_amount'] + $mandates[1]['total_amount'] + $mandates[2]['total_amount'];

            //collection stats
            $installments = Installment::whereHas('debitOrder', function ($query) use ($partner) {
                $query->where('debit_orders.partner_id', '=', $partner->id)
                    ->where('debit_orders.approved', '=', 1)
                    ->where('debit_orders.status', '=', 240);
            })->select(DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'), DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount'))->toBase();

            //monthly collection
            $monthly = $installments->whereMonth('payment_date', $month)->whereYear('payment_date', $year)->get();

            $table = '<table>';
            $table .= '<tr><td>Registered Mandate: </td>';
            $table .= '<td>' . number_format($total_mandate_value) . ' (' . number_format($total_number_of_mandate) . ')</td></tr>';

            $table .= '<tr><td>Approved Mandate: </td>';
            $table .= '<td>' . number_format($mandates[1]['total_amount']) . ' (' . number_format($mandates[1]['number_of_mandate']) . ')</td></tr>';

            $table .= '<tr><td>Pending Mandate: </td>';
            $table .= '<td>' . number_format($mandates[0]['total_amount']) . ' (' . number_format($mandates[0]['number_of_mandate']) . ')</td></tr>';

            $table .= '<tr><td>Failed Mandate: </td>';
            $table .= '<td>' . number_format($mandates[2]['total_amount']) . ' (' . number_format($mandates[2]['number_of_mandate']) . ')</td></tr>';

            $table .= '<tr><td>Expected Collection: </td>';
            $table .= '<td>' . number_format($monthly[0]->total_amount) . '</td></tr>';

            $table .= '<tr><td>Actual Collection: </td>';
            $table .= '<td>' . number_format($monthly[0]->paid_amount) . '</td></tr>';

            $table .= '</table>';

            //format date
            $month_year = date('F Y');

            //construct message
            $message = '<h4>Monthly Summary Report: ' . $month_year . '</h4>';
            $message .= $table;

            //partner staff 
            $staff = $partner->partnerStaff;

            if ($staff) {
                $data = [
                    "to_email" => $staff[0]->email,
                    "from_email" => "support@solocodes.io",
                    "name" => $staff[0]->name,
                    "subject" => "Monthly Summary Report: " . $month_year,
                    "body" => $message
                ];
                dispatch(new SendEmailJob($data));
            }
        }
    }
}
