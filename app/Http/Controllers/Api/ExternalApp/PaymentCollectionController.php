<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstallmentResource;
use App\Models\Installment;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentCollectionController extends Controller
{
    public function __construct() {}

    //all collections
    public function all(Request $request)
    {
        //current date
        $current_year = date('Y');
        $current_month = date('m');

        if ($request->has('year'))
            $current_year = $request->year;

        if ($request->has('month'))
            $current_month = $request->month;

        //collections
        $collections = Installment::filterCondition();

        //collection by date
        $collections = $collections
            ->with(['debitOrder'])
            ->whereMonth('installments.payment_date', $current_month)
            ->whereYear('installments.payment_date', $current_year);

        //apply status
        if ($request->has('status'))
            $collections = $collections->where('installments.status', $request->status); // OPEN, CLOSED, FUNDSCHECK, FAILED

        // mandate reference
        if ($request->reference)
            $collections = $collections->where('debit_orders.reference', $request->reference);
        
        //order
        $collections = $collections->orderBy('installments.payment_date', 'ASC')->get();

        return response()->json([
            'success' => true,
            'response_code' => 200,
            'collections' => InstallmentResource::collection($collections),
        ]);
    }

    public function actual_collections(Request $request)
    {
        //current date
        $current_year = date('Y');
        $current_month = date('m');

        if ($request->has('year'))
            $current_year = $request->year;

        if ($request->has('month'))
            $current_month = $request->month;

        //partners
        $partners = Partner::filterCondition()->where('status', 202)->get();

        $arr_data = [];
        foreach ($partners as $partner) {
            //collection stats
            $installments = Installment::select(
                DB::raw('COALESCE(COUNT(installments.id), 0) as number_of_collection'),
                DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'),
                DB::raw('COALESCE(SUM(installments.outstanding_amount), 0) as outstanding_amount'),
                DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount')
            )->whereHas('debitOrder', function ($query) use ($partner) {
                $query->where('debit_orders.partner_id', '=', $partner->id)
                    ->where('debit_orders.approved', '=', 1)
                    ->where('debit_orders.status', '=', 240);
            })->toBase();

            //this month collection
            $this_month = $installments
                ->whereMonth('installments.payment_date', $current_month)
                ->whereYear('installments.payment_date', $current_year)->get();

            //create dictionary
            if ($this_month[0]->paid_amount > 0) {
                $arr_data[] = [
                    'uuid' => $partner->uuid,
                    'partner_name' => $partner->business_name,
                    'number_of_collection' => $this_month[0]->number_of_collection,
                    'total_amount' => $this_month[0]->total_amount,
                    'outstanding_amount' => $this_month[0]->outstanding_amount,
                    'paid_amount' => $this_month[0]->paid_amount,
                    'percent' => round(($this_month[0]->paid_amount / $this_month[0]->total_amount) * 100, 2)
                ];
            }
        }

        //sort array
        $arr_data = collect($arr_data)->sortBy('number_of_collection')->reverse()->toArray();

        return response()->json([
            'success' => true,
            'response_code' => 200,
            'collections' => $arr_data,
        ]);
    }
}
