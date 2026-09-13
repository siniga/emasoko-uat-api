<?php

namespace App\Services;

use App\Classes\Authentication;
use App\Models\CollectionBatch;
use App\Models\CollectionBatchPushAttempt;
use App\Models\CollectionDebitOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            return $this->failure("Collection batch was not found.");
        }

        return $this->push($batch);
    }

    public function push(CollectionBatch $batch): array
    {
        $lock = Cache::lock("collection-batch:push:{$batch->id}", 120);

        if (!$lock->get()) {
            return $this->failure("This collection batch is already being pushed.", null, $batch->push_correlation_id);
        }

        $attempt = null;

        try {
            $batch = DB::transaction(function () use ($batch) {
                $lockedBatch = CollectionBatch::query()->lockForUpdate()->find($batch->id);

                if (!$lockedBatch || !$lockedBatch->canBePushed()) {
                    return null;
                }

                $lockedBatch->push_correlation_id = $lockedBatch->push_correlation_id ?: (string) Str::uuid();
                $lockedBatch->lifecycle_status = CollectionBatch::LIFECYCLE_QUEUED_FOR_PUSH;
                $lockedBatch->save();

                return $lockedBatch;
            });

            if (!$batch) {
                return $this->failure("Collection batch is not ready to be pushed.");
            }

            $correlationId = $batch->push_correlation_id;
            $attempt = CollectionBatchPushAttempt::create([
                "collection_batch_id" => $batch->id,
                "correlation_id" => $correlationId,
                "status" => "started",
            ]);
            $payload = [
                "user" => "EMakato",
                "correlationId" => $correlationId,
                "targetService" => "CollectionCreate",
                "status" => "Create",
                "data" => $this->bankPayloadService->collectionPayload($batch, $batch->request_csv),
            ];

            Log::info("Collection batch push started.", [
                "collection_batch_id" => $batch->id,
                "collection_reference" => $batch->collectionReference,
                "correlation_id" => $correlationId,
                "number_of_collections" => $batch->number_of_collections,
                "payload_hash" => hash("sha256", json_encode($payload)),
            ]);

            $response = Http::connectTimeout(10)
                ->timeout(60)
                ->withHeaders([
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer " . $this->auth->generateToken(),
                ])
                ->post(config("services.nmb.api_url"), $payload);

            return $this->recordResponse($batch, $attempt, $response->json(), $response->status());
        } catch (Throwable $exception) {
            if ($batch instanceof CollectionBatch && $batch->exists) {
                $batch->refresh();

                if ($batch->resolved_lifecycle_status === CollectionBatch::LIFECYCLE_QUEUED_FOR_PUSH) {
                    $this->markReadyAfterFailure($batch, null, "Bank connection failed. The batch remains ready to retry.");
                }
            }

            $attempt?->update([
                "status" => "transport_error",
                "message" => $exception->getMessage(),
            ]);

            Log::error("Collection batch push transport failure.", [
                "collection_batch_id" => $batch?->id,
                "collection_reference" => $batch?->collectionReference,
                "correlation_id" => $batch?->push_correlation_id,
                "exception_class" => $exception::class,
                "message" => $exception->getMessage(),
            ]);

            return $this->failure(
                "The bank connection did not respond. The batch was not marked as pushed and can be retried.",
                null,
                $batch?->push_correlation_id
            );
        } finally {
            $lock->release();
        }
    }

    protected function recordResponse(
        CollectionBatch $batch,
        CollectionBatchPushAttempt $attempt,
        ?array $response,
        int $httpStatus
    ): array {
        $responseCode = data_get($response, "data.response.0.responseCode");
        $remarks = data_get($response, "data.response.0.responseDescription")
            ?: data_get($response, "message")
            ?: "Bank returned an empty or invalid response.";
        $accepted = $this->isAcceptedResponse($response, $httpStatus);

        $attempt->update([
            "status" => $accepted ? "accepted" : "failed",
            "http_status" => $httpStatus,
            "response_code" => $responseCode,
            "message" => $remarks,
        ]);

        Log::info("Collection batch push response received.", [
            "collection_batch_id" => $batch->id,
            "collection_reference" => $batch->collectionReference,
            "correlation_id" => $batch->push_correlation_id,
            "http_status" => $httpStatus,
            "response_code" => $responseCode,
            "accepted" => $accepted,
        ]);

        if (!$accepted) {
            $this->markReadyAfterFailure($batch, $responseCode, $remarks);

            return $this->failure($remarks, $responseCode, $batch->push_correlation_id);
        }

        $batch->status = (int) $responseCode;
        $batch->http_response_code = $responseCode;
        $batch->remarks = $remarks;
        $batch->pushed = 1;
        $batch->last_pushed_at = now();
        $batch->lifecycle_status = CollectionBatch::LIFECYCLE_PUSHED;
        $batch->save();

        CollectionDebitOrder::where("collection_batch_id", $batch->id)->update([
            "status" => "PUSHED",
            "bank_status" => "PUSHED",
            "pushed_at" => now(),
        ]);

        try {
            $this->customerSmsNotificationService->queueCollectionBatchUploaded($batch->fresh());
        } catch (Throwable $exception) {
            Log::warning("Collection batch was accepted but its SMS notification could not be queued.", [
                "collection_batch_id" => $batch->id,
                "message" => $exception->getMessage(),
            ]);
        }

        return [
            "success" => true,
            "message" => $remarks,
            "response_code" => $responseCode,
            "correlation_id" => $batch->push_correlation_id,
        ];
    }

    public function isAcceptedResponse(?array $response, int $httpStatus): bool
    {
        $status = strtolower((string) data_get($response, "status"));
        $responseCode = (int) data_get($response, "data.response.0.responseCode", 0);

        return $httpStatus >= 200
            && $httpStatus < 300
            && $status === "success"
            && in_array($responseCode, [200, 201], true);
    }

    protected function markReadyAfterFailure(
        CollectionBatch $batch,
        int|string|null $responseCode,
        string $remarks
    ): void {
        if (is_numeric($responseCode)) {
            $batch->status = (int) $responseCode;
            $batch->http_response_code = (string) $responseCode;
        }

        $batch->remarks = $remarks;
        $batch->lifecycle_status = CollectionBatch::LIFECYCLE_READY;
        $batch->save();
    }

    protected function failure(
        string $message,
        int|string|null $responseCode = null,
        ?string $correlationId = null
    ): array {
        return [
            "success" => false,
            "message" => $message,
            "response_code" => $responseCode,
            "correlation_id" => $correlationId,
        ];
    }
}
