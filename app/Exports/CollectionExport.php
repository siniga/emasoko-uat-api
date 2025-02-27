<?php

namespace App\Exports;

use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        //installments
        $installments = Installment::filterCondition();

        if ($this->request->has('start_at') != null && $this->request->has('end_at') != null) {
            if ($this->request->input('start_at') != null && $this->request->input('end_at') == null) {
                $installments = $installments->whereDate('installments.payment_date', '=', $this->request->input('start_at'));
            } else if ($this->request->input('start_at') != null && $this->request->input('end_at') != null) {
                $installments = $installments->whereBetween('installments.payment_date', [$this->request->input('start_at'), $this->request->input('end_at')]);
            }
        } else {
            $installments = $installments->whereDate('installments.payment_date', '<=', Carbon::now());
        }

        if ($this->request->has('partner_id') != null) {
            $partner_id = $this->request->input('partner_id');

            $installments = $installments->whereHas("debitOrder", function ($q) use ($partner_id) {
                $q->where('partner_id', $partner_id);
            });
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
            'Service Provider',
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
            strtoupper($installments->debitOrder->partner->business_name),
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
                $cellRange = 'A1:L1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
}
