<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendReportSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:report-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send report summary to eMakato Managers';

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
        $approvedStatuses = [0, 1, 2];

        //query
        $debit_orders = DebitOrder::select('debit_orders.approved', DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'), DB::raw('COUNT(id) as number_of_mandate'))
            ->where(function ($query) {
                $query->whereIn('debit_orders.approved', [0, 1, 2]);
            })->groupBy('debit_orders.approved')->orderBy('debit_orders.approved')->toBase();

        $debit_orders = $debit_orders->whereDate('debit_orders.created_at', '=', Carbon::now())->get();

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
        $installments = Installment::whereHas('debitOrder')->select(DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'), DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount'))->toBase();

        //today collection
        $today = $installments->whereDate('payment_date', Carbon::now())->get();

        $table = '<table>';
        $table .= '<tr><td>Registered Mandate</td>';
        $table .= '<td>' . number_format($total_mandate_value) . ' (' . number_format($total_number_of_mandate) . ')</td></tr>';

        $table .= '<tr><td>Approved Mandate</td>';
        $table .= '<td>' . number_format($mandates[1]['total_amount']) . ' (' . number_format($mandates[1]['number_of_mandate']) . ')</td></tr>';

        $table .= '<tr><td>Pending Mandate</td>';
        $table .= '<td>' . number_format($mandates[0]['total_amount']) . ' (' . number_format($mandates[0]['number_of_mandate']) . ')</td></tr>';

        $table .= '<tr><td>Failed Mandate</td>';
        $table .= '<td>' . number_format($mandates[2]['total_amount']) . ' (' . number_format($mandates[2]['number_of_mandate']) . ')</td></tr>';

        $table .= '<tr><td>Expected Collection</td>';
        $table .= '<td>' . number_format($today[0]->total_amount) . '</td></tr>';

        $table .= '<tr><td>Actual Collection</td>';
        $table .= '<td>' . number_format($today[0]->paid_amount) . '</td></tr>';

        $table .= '</table>';

        //construct message
        $message = '<h4>Daily Summary Report - ' . Carbon::now()->format('d-m-Y') . '</h4>';
        $message .= $table;

        //users 
        // $users = User::whereIn('id', [37, 38])->get();

        // foreach ($users as $value) {
        //     $data = [
        //         "to_email" => $value->email,
        //         "from_email" => "support@solocodes.io",
        //         "name" => $value->name,
        //         "subject" => "Daily Summary Report - " . Carbon::now()->format('d-m-Y'),
        //         "body" => $message
        //     ];
        //     dispatch(new SendEmailJob($data));
        // }

        $data = [
            "to_email" => "renfridfrancis@gmail.com",
            "from_email" => "support@solocodes.io",
            "name" => "Renfrid",
            "subject" => "Daily Summary Report - " . Carbon::now()->format('d-m-Y'),
            "body" => $message
        ];
        dispatch(new SendEmailJob($data));
    }
}
