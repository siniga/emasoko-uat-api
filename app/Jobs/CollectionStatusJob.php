<?php

namespace App\Jobs;

use App\Classes\Authentication;
use App\Events\CollectionStatus;
use App\Models\CollectionBatch;
use App\Services\DeductionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CollectionStatusJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(protected string $batchId)
    {
    }

    public function uniqueId(): string
    {
        return $this->batchId;
    }

    public function handle(Authentication $auth, DeductionService $deductionService): void
    {
        $batch = CollectionBatch::find($this->batchId);

        if (!$batch || !$batch->canFetchStatus()) {
            return;
        }

        $batch->lifecycle_status = CollectionBatch::LIFECYCLE_STATUS_PENDING;
        $batch->save();
        $correlationId = (string) Str::uuid();

        try {
            $httpResponse = Http::connectTimeout(10)
                ->timeout(60)
                ->withHeaders([
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer " . $auth->generateToken(),
                ])
                ->post(config("services.nmb.api_url"), [
                    "user" => "EMakato",
                    "correlationId" => $correlationId,
                    "targetService" => "CollectionStatus",
                    "status" => "Check",
                    "data" => [
                        "collectionReference" => $batch->collectionReference,
                    ],
                ]);

            $response = $httpResponse->json();
            if (!is_array($response)) {
                throw new \RuntimeException("Bank returned an invalid JSON response.");
            }

            Log::info("Collection status response received.", [
                "collection_batch_id" => $batch->id,
                "collection_reference" => $batch->collectionReference,
                "correlation_id" => $correlationId,
                "http_status" => $httpResponse->status(),
                "bank_status" => data_get($response, "status"),
                "response_code" => data_get($response, "data.response.0.responseCode"),
            ]);

            $this->applyResponse($batch, $response, $deductionService);
            event(new CollectionStatus($batch->fresh()));
        } catch (Throwable $exception) {
            Log::error("Collection status request failed.", [
                "collection_batch_id" => $batch->id,
                "collection_reference" => $batch->collectionReference,
                "correlation_id" => $correlationId,
                "exception_class" => $exception::class,
                "message" => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    protected function applyResponse(CollectionBatch $batch, array $response, DeductionService $deductionService): void
    {
        $status = strtolower((string) data_get($response, "status"));
        $responseCode = data_get($response, "data.response.0.responseCode");
        $remarks = data_get($response, "data.response.0.responseDescription")
            ?: data_get($response, "message")
            ?: "Collection status response received.";
        $payloadHash = hash("sha256", json_encode($response));

        if (hash_equals((string) $batch->status_payload_hash, $payloadHash)) {
            $batch->last_status_checked_at = now();
            $batch->save();
            return;
        }

        if (is_numeric($responseCode)) {
            $batch->status = (int) $responseCode;
            $batch->http_response_code = (string) $responseCode;
        }
        $batch->remarks = $remarks;
        $batch->last_status_checked_at = now();

        if ($status !== "success") {
            if ((int) $responseCode === 235) {
                $batch->processed = 1;
                $batch->failed = 1;
                $batch->lifecycle_status = CollectionBatch::LIFECYCLE_PROCESSED_FAILED;
            }
            $batch->status_payload_hash = $payloadHash;
            $batch->save();
            return;
        }

        $fileData = data_get($response, "data.file.fileData");
        if (in_array((int) $responseCode, [230, 235, 239], true) && empty($fileData)) {
            throw new \RuntimeException("Bank collection status response did not contain the result file.");
        }

        if ((int) $responseCode === 230) {
            [$successful, $failed] = $deductionService->updateInstallments($batch, base64_to_content($fileData));
            $batch->number_of_success = $successful;
            $batch->number_of_failed = $failed;
            $batch->processed = 1;
            $batch->failed = 0;
            $batch->lifecycle_status = CollectionBatch::LIFECYCLE_PROCESSED_SUCCESS;
        } elseif ((int) $responseCode === 235) {
            [$failed] = $deductionService->updateFailedInstallments($batch, base64_to_content($fileData));
            $batch->number_of_failed = $failed;
            $batch->processed = 1;
            $batch->failed = 1;
            $batch->lifecycle_status = CollectionBatch::LIFECYCLE_PROCESSED_FAILED;
        } elseif ((int) $responseCode === 239) {
            [$successful, $failed] = $deductionService->updateInstallments($batch, base64_to_content($fileData));
            $batch->number_of_success = $successful;
            $batch->number_of_failed = $failed;
            $batch->processed = 0;
            $batch->lifecycle_status = CollectionBatch::LIFECYCLE_PARTIALLY_PROCESSED;
        }

        $batch->status_payload_hash = $payloadHash;
        $batch->save();
    }
}
