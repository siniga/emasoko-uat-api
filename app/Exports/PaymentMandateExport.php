<?php

namespace App\Exports;

use App\Models\DebitOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;

class PaymentMandateExport implements FromQuery, WithHeadings, ShouldAutoSize, WithEvents, WithMapping
{
    protected $start_at, $end_at, $account_type_id, $account_id, $status, $keyword;

    public function __construct($start_at, $end_at, $account_type_id, $account_id, $status = null, $keyword = null)
    {
        $this->start_at = $start_at;
        $this->end_at = $end_at;
        $this->account_type_id = $account_type_id;
        $this->account_id = $account_id;
        $this->status = $status;
        $this->keyword = $keyword;
    }

    public function query()
    {
        //debit
        $debit_orders = DebitOrder::where_cond();
        //$debit_orders = DebitOrder::query();

        //join
        $debit_orders = $debit_orders->join('customer_accounts', 'customer_accounts.id', '=', 'debit_orders.customer_account_id');

        //start data and end date
        if ($this->start_at != null && $this->end_at != null) {
            $start_at = date('Y-m-d', strtotime($this->start_at));
            $end_at = date('Y-m-d', strtotime($this->end_at));

            $debit_orders = $debit_orders->whereBetween('debit_orders.created_at', [$start_at, $end_at]);
        }

        //payment channel
        if ($this->account_type_id != null) {
            $debit_orders = $debit_orders->where('customer_accounts.account_type_id', $this->account_type_id);
        }

        //bank account
        if ($this->account_id != null) {
            $debit_orders = $debit_orders->where('customer_accounts.account_id', $this->account_id);
        }

        //status
        if ($this->status != null)
            $debit_orders = $debit_orders->where('debit_orders.approved', $this->status);

        //keyword
        if ($this->keyword != null) {
            $keyword = $this->keyword;

            //query
            $debit_orders = $debit_orders->where(function ($query) use ($keyword) {
                $query->orWhere('customer_accounts.account_name', 'LIKE', "%$keyword%")
                    ->orWhere('customer_accounts.account_number', 'LIKE', "%$keyword%");
            });
        }


        //return
        return $debit_orders;
    }

    //headings
    public function headings(): array
    {
        return [
            'Account Name',
            'NIDA Number',
            'Payment Channel',
            'Bank Name/MNO',
            'Account Number',
            'Phone (Connected to Bank)',
            'Consent Status',
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
            'Created On',
        ];
    }

    //map
    public function map($debit_orders): array
    {
        return [
            strtoupper($debit_orders->customerAccount->account_name ?? ''),
            $debit_orders->customerAccount->customer->id_number ?? 'NIL',
            $debit_orders->customerAccount->accountType->name ?? '',
            $debit_orders->customerAccount->account->name ?? '',
            $debit_orders->customerAccount->account_number ?? '',
            $debit_orders->customerAccount->phone ?? '',
            $debit_orders->approved == 1 ? 'Approved' : 'Pending',
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
            $debit_orders->created_at,
        ];

    }

    //events
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $cellRange = 'A1:R1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
}
