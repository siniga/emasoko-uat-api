<?php

namespace App\Http\Controllers\Api\Crdb;

use App\Http\Controllers\Controller;
use App\Http\Requests\BatchDisbursementRequest;
use App\Http\Requests\BatchRecord;
use App\Models\DisbursementBatch;
use App\Services\DisbursementApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DisbursementController extends Controller
{
    public function __construct() {}

    public function postBatch(DisbursementApiService $svc)
    {
        //create batch and records
        $data = [
            "batchAccount" => "01J000000000",
            "batchSender" => "Solocodes",
            "batchDesc" => "Service Provider 04-03-2026",
            "batchApproval" => "N",
            "records" => [
                [
                    "recAccount" => "0000T5534534",
                    "recBic" => "CORUTZTZ",
                    "recName" => "Michael Shaka",
                    "recRef" => "REF001",
                    "recSecRef" => "SEC001",
                    "recAmount" => 100000
                ],
                [
                    "recAccount" => "0J2005534000",
                    "recBic" => "CORUTZTZ",
                    "recName" => "Anna Mwakyusa",
                    "recRef" => "REF002",
                    "recSecRef" => "SEC002",
                    "recAmount" => 150000
                ]
            ]
        ];

        //create batch ID
        $batchID = DisbursementApiService::newBatchId('KCS00');

        $records = [];
        $total = 0;
        foreach ($data['records'] as $r) {
            $recAmount = (float) $r['recAmount'];
            $total += $recAmount;

            $records[] = new BatchRecord(
                recID: DisbursementApiService::newRecId(),
                recAccount: $r['recAccount'],
                recBic: $r['recBic'] ?? 'CORUTZTZ',
                recName: $r['recName'] ?? '',
                recRef: $r['recRef'] ?? '',
                recSecRef: $r['recSecRef'] ?? '',
                recAmount: $recAmount,
                recCurrency: 'TZS',
                recDesc: 'Loan Disbursement'
            );
        }

        $dto = new BatchDisbursementRequest(
            batchID: $batchID,
            batchAccount: $data['batchAccount'],
            batchSender: $data['batchSender'],
            batchDesc: $data['batchDesc'],
            batchTotalAmount: $total,
            records: $records,
            batchCurrency: 'TZS',
            batchPostType: count($records) === 1 ? 'S' : 'M', // S||M :contentReference[oaicite:19]{index=19}
            batchApproval: $data['batchApproval'] ?? 'N'
        );

        return $dto;

        //post the batch
        $batch = $svc->createAndPost($dto);

        return response()->json([
            'status' => 200,
            'statusDesc' => 'success',
            'data' => [
                'batchID' => $batch->batch_id,
                'txnReference' => $batch->txn_reference,
                'internalStatus' => $batch->status,
            ]
        ]);
    }

    public function callback(Request $request)
    {
        Log::channel('apiLogs')->info('CRDB Disbursement Callback', $request->all());

        $data = $request->validate([
            'batchCode'    => 'required|string', // CRDBBTCMP or CRDBBTFLD :contentReference[oaicite:21]{index=21}
            'batchID'      => 'required|string',
            'txnReference' => 'required|string',
            'recID'        => 'required|string',
            'partnerID'    => 'required|string',
            'recDesc'      => 'nullable|string',
            'channel'      => 'nullable|string',
        ]);

        $batch = DisbursementBatch::where('batch_id', $data['batchID'])->first();

        if ($batch) {
            $batch->update([
                'txn_reference' => $data['txnReference'],
                'status' => $data['batchCode'] === 'CRDBBTCMP' ? 'COMPLETED' : 'FAILED',
            ]);

            $batch->records()
                ->where('rec_id', $data['recID'])
                ->update([
                    'status' => $data['batchCode'] === 'CRDBBTCMP' ? 'COMPLETED' : 'FAILED',
                    'status_desc' => $data['recDesc'] ?? null,
                ]);
        }

        // Partner must return a success/receipt style response (same idea as your current method).
        return response()->json([
            'status' => 200,
            'statusDesc' => 'success',
            'data' => [
                'receipt' => 'KCS' . rand(1000000000, 99999999999),
            ]
        ]);
    }
}
