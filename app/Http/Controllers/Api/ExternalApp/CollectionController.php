<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Controller;
use App\Models\Installment;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
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
            ->whereMonth('payment_date', $current_month)
            ->whereYear('payment_date', $current_year);

        //order
        $collections = $collections->orderBy('payment_date', 'ASC')->get();    

        return response()->json([
            'success' => true,
            'response_code' => 200,
            'collections' => $collections,
        ]);
    }
}
