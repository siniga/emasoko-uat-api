<?php

namespace App\Exports;

use App\Models\DebitOrder;
use Illuminate\Support\Facades\DB;
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
        //debit orders
        $debit_orders = DebitOrder::filterCondition();

        //start data and end date
        if ($this->start_at != null && $this->end_at != null) {
            if ($this->start_at != null && $this->end_at != null)
                $debit_orders = $debit_orders->whereBetween('debit_orders.created_at', [$this->start_at, $this->end_at]);

            else if ($this->start_at != null)
                $debit_orders = $debit_orders->where('debit_orders.created_at', '>=', $this->start_at);

            else if ($this->end_at != null)
                $debit_orders = $debit_orders->where('debit_orders.created_at', '<=', $this->end_at);
        }

        if ($this->account_type_id != null) {
            $account_type_id = $this->account_type_id;

            $debit_orders = $debit_orders->whereHas("customerAccount", function ($q) use ($account_type_id) {
                $q->where('customer_accounts.account_type_id', $account_type_id);
            });
        }

        if ($this->account_id != null) {
            $account_id = $this->account_id;

            $debit_orders = $debit_orders->whereHas("customerAccount", function ($q) use ($account_id) {
                $q->where('customer_accounts.account_id', $account_id);
            });
        }

        if ($this->status != null)
            $debit_orders = $debit_orders->where('debit_orders.approved', $this->status);

        if ($this->keyword != null) {
            $keyword = $this->keyword;

            $debit_orders = $debit_orders->whereHas("customerAccount", function ($q) use ($keyword) {
                $q->where(DB::raw('LOWER(account_name)'), 'like', "%" . strtolower($keyword) . "%")
                    ->orWhere(DB::raw('LOWER(account_number)'), 'like', "%" . strtolower($keyword) . "%");
            });
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
            'Created Date',
            'Created By',
        ];
    }

    //map
    public function map($debit_orders): array
    {
        //format status
        if ($debit_orders->approved == 0)
            $consent_status = "PENDING";
        else if($debit_orders->approved == 1)
            $consent_status = "APPROVED";
        else if($debit_orders->approved == 1)
            $consent_status = "FAILED";

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
            $debit_orders->created_at,
            $debit_orders->userCreated->name,
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
