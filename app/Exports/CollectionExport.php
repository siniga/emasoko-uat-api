<?php

namespace App\Exports;

use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;

class CollectionExport implements FromQuery, WithHeadings, ShouldAutoSize, WithEvents, WithMapping
{
    protected $start_at, $end_at;

    public function __construct($start_at, $end_at)
    {
        $this->start_at = $start_at;
        $this->end_at = $end_at;
    }

    public function query()
    {
        //installments
        $installments = Installment::filterCondition();

        if ($this->start_at == null && $this->end_at == null) {
            $installments = $installments->whereDate('installments.payment_date', '<=', Carbon::now());
        } else {
            if ($this->start_at != null && $this->end_at == null) {
                $installments = $installments->whereDate('installments.payment_date', '=', $this->start_at);
            } else if ($this->start_at != null && $this->end_at != null) {
                $installments = $installments->whereBetween('installments.payment_date', [$this->start_at, $this->end_at]);
            }
        }

        //status
        $installments = $installments->where(['pushed' => 1]);

        //order installments
        $installments = $installments->orderBy('installments.created_at', 'ASC');

        //return
        return $installments;
    }

    //headings
    public function headings(): array
    {
        return [
            'Mandate Reference',
            'Account Name',
            'Bank Name/MNO',
            'Account Number',
            'Phone',
            'Installment Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Payment Status',
            'Collection Date',
            'Updated Date',
        ];
    }

    //map
    public function map($installments): array
    {
        $paid = "UNPAID";
        if ($installments->status == "OPEN")
            $paid = "UNPAID";
        elseif ($installments->status == "CLOSED")
            $paid = "PAID";
        elseif ($installments->status == "PARTIAL_PAID")
            $paid = "PARTIAL PAID";

        return [
            $installments->debitOrder->reference ?? '',
            strtoupper($installments->debitOrder->customerAccount->account_name ?? ''),
            $installments->debitOrder->customerAccount->account->name ?? '',
            $installments->debitOrder->customerAccount->account_number ?? '',
            $installments->debitOrder->customerAccount->phone ?? '',
            number_format($installments->installment_amount),
            number_format($installments->current_balance),
            number_format($installments->outstanding_amount),
            $paid,
            $installments->payment_date,
            $installments->updated_at
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $cellRange = 'A1:K1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
}
