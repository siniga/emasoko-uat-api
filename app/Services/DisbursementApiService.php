<?php

namespace App\Services;

use App\Classes\CrdbSession;
use App\Http\Requests\BatchDisbursementRequest;
use App\Models\DisbursementBatch;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DisbursementApiService
{
    public function __construct(
        private CrdbSession $session
    ) {}

    public function createAndPost(BatchDisbursementRequest $dto): DisbursementBatch
    {
        $partnerID = env("CRDB_SESSION_PARTNER_ID");
        $token     = $this->session->getToken();

        //headers
        $headers = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ];

        //create payload
        $payload = $dto->toArray($token, $partnerID);

        // Persist BEFORE sending (idempotency, retries)
        $batch = DisbursementBatch::create([
            'batch_id'         => $dto->batchID,
            'batch_code'       => $dto->batchCode, // CRDBBTDIS :contentReference[oaicite:17]{index=17}
            'status'           => 'PENDING',
            'total_amount'     => $dto->batchTotalAmount,
            'currency'         => $dto->batchCurrency,
            'request_payload'  => $payload,
        ]);

        foreach ($dto->records as $r) {
            $batch->records()->create([
                'rec_id'       => $r->recID,
                'rec_account'  => $r->recAccount,
                'rec_bic'      => $r->recBic,
                'rec_name'     => $r->recName,
                'rec_ref'      => $r->recRef,
                'rec_sec_ref'  => $r->recSecRef,
                'rec_amount'   => $r->recAmount,
                'rec_currency' => $r->recCurrency,
                'status'       => 'PENDING',
            ]);
        }

        //API URL
        $API_URL = env("CRDB_BATCH_URL");

        //push payload 
        $response = Http::withHeaders($headers)->post($API_URL, $payload);
        Log::debug("== Disbsursement Response == ");
        Log::debug($response);


        // If session expired, refresh once
        // if ($res->status() === 401 || $res->status() === 403) {
        //     $this->session->forgetToken();
        //     $token = $this->session->getToken();
        //     $payload = $dto->toArray($token, $partnerID);
        //     $res = $this->client->postJson(config('crdb.urls.batch'), $payload);
        // }

        // $body = $res->json();

        // $batch->update([
        //     'response_payload' => $body,
        //     'txn_reference'    => data_get($body, 'data.txnReference'), // response sample :contentReference[oaicite:18]{index=18}
        //     'status'           => $res->ok() ? 'POSTED' : 'FAILED',
        // ]);

        // if (!$res->ok()) {
        //     throw new RuntimeException('CRDB POST BATCH TXNS failed');
        // }

        return $batch;
    }

    public function requestReversal(string $batchID): void
    {
        $batch = DisbursementBatch::where('batch_id', $batchID)->firstOrFail();

        if ($batch->status !== 'PENDING' && $batch->status !== 'POSTED') {
            throw new RuntimeException("Batch not eligible for reversal.");
        }

        //headers
        $headers = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ];

        $token = $this->session->getToken();

        // NOTE: confirm exact payload format for CRDBBTSRV in your PDF section,
        // but code name is explicitly referenced. :contentReference[oaicite:23]{index=23}
        $payload = [
            'batch' => array_merge($batch->request_payload['batch'], [
                'batchCode' => 'CRDBBTSRV',
                'batchID'   => $batch->batch_id,
            ]),
            'records'      => $batch->request_payload['records'],
            'sessionToken' => $token,
            'partnerID'    => config('crdb.partner_id'),
        ];

        //API URL
        $API_URL = env("CRDB_BATCH_URL");

        //push payload 
        $response = Http::withHeaders($headers)->post($API_URL, $payload);
        Log::debug("== Disbsursement Response == ");
        Log::debug($response);

        // $batch->update([
        //     'response_payload' => $response->json(),
        //     'status' => $response->ok() ? 'REVERSED_REQUESTED' : 'FAILED',
        // ]);
    }

    public static function newBatchId(string $prefix = 'KCS'): string
    {
        return $prefix . Str::upper(Str::random(12));
    }

    public static function newRecId(): string
    {
        return (string) Str::uuid();
    }
}
