<?php

namespace App\Services;

use App\Classes\Authentication;
use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CollectionBatchPushService
{
    public function __construct(
        protected Authentication $auth,
        protected BankPayloadService $bankPayloadService,
        protected CustomerSmsNotificationService $customerSmsNotificationService
    ) {
    }

    public function pushById(string $batchId): array
    {
        $batch = CollectionBatch::find($batchId);

        if (!$batch) {
            Log::warning('Collection batch push skipped: batch not found.', [
                'collection_batch_id' => $batchId,
            ]);

            return [
                'success' => false,
                'message' => 'Collection batch was not found.',
                'response_code' => null,
                'correlation_id' => null,
            ];
        }

        return $this->push($batch);
    }

    public function push(CollectionBatch $batch): array
    {
        $batch->refresh();

        Log::debug("== Pushing collection batch with reference: {$batch->collectionReference} ==");

        if (!$batch->canBePushed()) {
            $message = empty($batch->request_csv)
                ? 'Collection batch has no request CSV.'
                : "Collection batch cannot be pushed from status {$batch->resolved_lifecycle_status}.";

            Log::warning('Collection batch push skipped.', [
                'collection_batch_id' => $batch->id,
                'collection_reference' => $batch->collectionReference,
                'lifecycle_status' => $batch->resolved_lifecycle_status,
                'message' => $message,
            ]);

            return [
                'success' => false,
                'message' => $message,
                'response_code' => null,
                'correlation_id' => null,
            ];
        }

        $batch->lifecycle_status = CollectionBatch::LIFECYCLE_QUEUED_FOR_PUSH;
        $batch->save();

        $correlationId = Str::uuid()->toString();

        try {
            $payload = [
                'user' => 'EMakato',
                'correlationId' => $correlationId,
                'targetService' => 'CollectionCreate',
                'status' => 'Create',
                'data' => $this->bankPayloadService->collectionPayload($batch, $batch->request_csv),
            ];

            Log::debug('== CollectionCreate Request ==', $payload);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->auth->generateToken(),
            ])->post(config('services.nmb.api_url'), $payload);

            Log::debug('== CollectionCreate HTTP Response ==', [
                'collection_batch_id' => $batch->id,
                'collection_reference' => $batch->collectionReference,
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ]);

            return $this->recordResponse($batch, $response->json(), $response->status(), $correlationId);
        } catch (Throwable $exception) {
            $batch->lifecycle_status = CollectionBatch::LIFECYCLE_READY;
            $batch->remarks = $exception->getMessage();
            $batch->save();

            Log::error('Collection batch push failed.', [
                'collection_batch_id' => $batch->id,
                'collection_reference' => $batch->collectionReference,
                'correlation_id' => $correlationId,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'response_code' => null,
                'correlation_id' => $correlationId,
            ];
        }
    }

    protected function recordResponse(CollectionBatch $batch, ?array $response, int $httpStatus, string $correlationId): array
    {
        Log::debug('== CollectionCreate Response ==', $response ?? []);

        if (!$response) {
            $message = 'Bank returned an empty or invalid JSON response.';
            $this->markReadyAfterFailure($batch, $httpStatus, $message);

            return [
                'success' => false,
                'message' => $message,
                'response_code' => $httpStatus,
                'correlation_id' => $correlationId,
            ];
        }

        $status = strtolower((string) data_get($response, 'status'));
        $responseCode = data_get($response, 'data.response.0.responseCode', $httpStatus);
        $remarks = data_get($response, 'data.response.0.responseDescription')
            ?: data_get($response, 'message')
            ?: 'Collection batch push response received.';

        if ($status === 'success') {
            $batch->status = $responseCode;
            $batch->http_response_code = $responseCode;
            $batch->remarks = $remarks;
            $batch->pushed = 1;
            $batch->last_pushed_at = now();
            $batch->push_correlation_id = $correlationId;
            $batch->lifecycle_status = CollectionBatch::LIFECYCLE_PUSHED;
            $batch->save();

            if (in_array((int) $responseCode, [200, 201], true)) {
                CollectionDebitOrder::where('collection_batch_id', $batch->id)->update([
                    'status' => 'PUSHED',
                    'bank_status' => 'PUSHED',
                    'pushed_at' => now(),
                ]);

                $this->customerSmsNotificationService->queueCollectionBatchUploaded($batch->fresh());
            }

            return [
                'success' => true,
                'message' => $remarks,
                'response_code' => $responseCode,
                'correlation_id' => $correlationId,
            ];
        }

        $this->markReadyAfterFailure($batch, $responseCode, $remarks);

        return [
            'success' => false,
            'message' => $remarks,
            'response_code' => $responseCode,
            'correlation_id' => $correlationId,
        ];
    }

    protected function markReadyAfterFailure(CollectionBatch $batch, int|string|null $responseCode, string $remarks): void
    {
        $batch->status = 500;
        $batch->http_response_code = $responseCode;
        $batch->remarks = $remarks;
        $batch->lifecycle_status = CollectionBatch::LIFECYCLE_READY;
        $batch->save();
    }
}
