<?php

namespace App\Exports;

use App\Models\CustomerAccount;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromArray;

class CustomerAccountExport implements FromQuery, WithHeadings, ShouldAutoSize, WithEvents, WithMapping
{
    protected $start_at, $end_at, $account_type_id, $account_id, $status, $keyword;

    public function __construct($start_at, $end_at, $account_type_id, $account_id, $status, $keyword)
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
        //customer accounts
        $customer_accounts = CustomerAccount::where_cond();

        //start data and end date
        if ($this->start_at != null && $this->end_at != null) {
            $start_at = date('Y-m-d', strtotime($this->start_at));
            $end_at = date('Y-m-d', strtotime($this->end_at));

            $customer_accounts = $customer_accounts->whereBetween('customer_accounts.created_at', [$start_at, $end_at]);
        }

        //payment channel
        if ($this->account_type_id != null) {
            $customer_accounts = $customer_accounts->where('customer_accounts.account_type_id', $this->account_type_id);
        }

        //bank account
        if ($this->account_id != null) {
            $customer_accounts = $customer_accounts->where('customer_accounts.account_id', $this->account_id);
        }

        //status
        if ($this->status != null)
            $customer_accounts = $customer_accounts->where('customer_accounts.status', $this->status);

        //keyword
        if ($this->keyword != null) {
            $keyword = $this->keyword;

            //query
            $customer_accounts = $customer_accounts->where(function ($query) use ($keyword) {
                $query->orWhere('customer_accounts.account_name', 'LIKE', "%$keyword%")
                    ->orWhere('customer_accounts.account_number', 'LIKE', "%$keyword%");
            });
        }

        //return customer accounts
        return $customer_accounts;
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
            'Status',
            'Remarks',
            'Created'
        ];
    }

    //map
    public function map($customer_accounts): array
    {
        return [
            $customer_accounts->account_name == null ? $customer_accounts->phone : $customer_accounts->account_name,
            $customer_accounts->customer->id_number ?? 'NIL',
            $customer_accounts->accountType->name ?? '',
            $customer_accounts->account->name ?? '',
            $customer_accounts->account_number ?? $customer_accounts->phone,
            $customer_accounts->status == 'approved' ? 'Approved' : 'Partial Approved',
            $customer_accounts->remarks ?? '',
            $customer_accounts->created_at,
        ];
    }

    //events
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $cellRange = 'A1:H1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
}
