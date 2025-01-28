<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\DebitOrder;
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

        $table = '<table border=1>';
        $table .= '<tr><td>Registered Mandate</td>';
        $table .= '<td>' . $mandates[0]['number_of_mandate'] . '</td></tr>';

        $table .= '<tr><td>Approved Mandate</td>';
        $table .= '<td>' . $mandates[1]['number_of_mandate'] . '</td></tr>';

        $table .= '<tr><td>Pending Mandate</td>';
        $table .= '<td>' . $mandates[0]['number_of_mandate'] . '</td></tr>';

        $table .= '<tr><td>Failed Mandate</td>';
        $table .= '<td>' . $mandates[2]['number_of_mandate'] . '</td></tr>';

        $table .= '<tr><td>Expected Collection</td>';
        $table .= '<td>0</td></tr>';

        $table .= '<tr><td>Actual Collection</td>';
        $table .= '<td>0</td></tr>';

        $table .= '</table>';

        //construct message
        $message = '<h3>Daily Report Summary - ' . Carbon::now()->format('d-m-Y') . '</h3>';
        $message .= $table;

        //send email
        $data = [
            "to_email" => "renfridfrancis@gmail.com",
            "from_email" => "support@solocodes.io",
            "name" => "Renfrid",
            "subject" => "Daily Report Summary",
            "body" => $message
        ];
        dispatch(new SendEmailJob($data));
    }
}
