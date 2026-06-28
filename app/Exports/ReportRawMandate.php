<?php
namespace App\Exports;

use App\Models\DebitOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;

class ReportRawMandate implements FromQuery, WithHeadings, ShouldAutoSize, WithEvents, WithMapping
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        //debit orders
        $debit_orders = DebitOrder::filterCondition();

        //start data and end date
        if ($this->request->has('start_at') != null && $this->request->has('end_at') != null) {
            if ($this->request->input('start_at') != null && $this->request->input('end_at') != null)
                $debit_orders = $debit_orders->whereBetween('debit_orders.created_at', [$this->request->input('start_at'), $this->request->input('end_at')]);

            else if ($this->request->input('start_at') != null)
                $debit_orders = $debit_orders->where('debit_orders.created_at', '>=', $this->request->input('start_at'));

            else if ($this->request->input('end_at') != null)
                $debit_orders = $debit_orders->where('debit_orders.created_at', '<=', $this->request->input('end_at'));
        }

        if ($this->request->has('account_type_id') != null) {
            $account_type_id = $this->request->input('account_type_id');

            $debit_orders = $debit_orders->whereHas("customerAccount", function ($q) use ($account_type_id) {
                $q->where('customer_accounts.account_type_id', $account_type_id);
            });
        }

        if ($this->request->has('partner_id') != null) {
            $debit_orders = $debit_orders->where('debit_orders.partner_id', $this->request->input('partner_id'));
        }

        if ($this->request->has('account_id') != null) {
            $account_id = $this->request->input('account_id');

            $debit_orders = $debit_orders->whereHas("customerAccount", function ($q) use ($account_id) {
                $q->where('customer_accounts.account_id', $account_id);
            });
        }

        if ($this->request->has('status') != null) {
            $debit_orders = $debit_orders->whereIn(
                'debit_orders.lifecycle_status',
                DebitOrder::lifecycleStatusesForSummary($this->request->input('status'))
            );
        }

        $debit_orders = $debit_orders->orderBy('debit_orders.created_at', 'DESC');

        //return
        return $debit_orders;
    }

    //headings
    public function headings(): array
    {
        return [
            'Account Name',
            'NIDA Number',
            'Address',
            'Payment Channel',
            'Bank Name/MNO',
            'Account Number',
            'Phone (Connected to Bank)',
            'Mandate Status',
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Deduction Amount',
            'Debit Type',
            'Number of Installment',
            'Frequency of Installment',
            'Deduction Start Date',
            'Deduction End Date',
            'Contract Status',
            'Remarks',
            'Created Date',
            'Created By',
            'Service Provider',
        ];
    }

    //map
    public function map($debit_orders): array
    {
        //format status
        $consent_status = match ($debit_orders->summary_status) {
            DebitOrder::SUMMARY_APPROVED => 'APPROVED',
            DebitOrder::SUMMARY_FAILED => 'FAILED',
            default => 'PENDING',
        };

        return [
            strtoupper($debit_orders->customerAccount->account_name ?? ''),
            $debit_orders->customerAccount->customer->id_number ?? '',
            $debit_orders->customerAccount->customer->address ?? '',
            $debit_orders->customerAccount->accountType->name ?? '',
            $debit_orders->customerAccount->account->name ?? '',
            $debit_orders->customerAccount->account_number ?? '',
            $debit_orders->customerAccount->phone ?? '',
            $consent_status,
            number_format($debit_orders->total_amount),
            number_format($debit_orders->current_balance),
            number_format($debit_orders->outstanding_amount),
            $debit_orders->installment_amount == 0 ? number_format($debit_orders->max_installment_amount) : number_format($debit_orders->installment_amount),
            $debit_orders->debit_type,
            $debit_orders->installment_number,
            $debit_orders->periodicity,
            $debit_orders->start_date,
            $debit_orders->end_date,
            $debit_orders->contract_status,
            $debit_orders->remarks,
            $debit_orders->created_at,
            strtoupper($debit_orders->userCreated->name),
            strtoupper($debit_orders->partner->business_name),
        ];
    }

    //events
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $cellRange = 'A1:V1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
}
