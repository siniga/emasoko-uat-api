<?php

namespace App\Http\Controllers;

use App\Models\DebitOrder;
use App\Services\CollectionBatchPushService;
use App\Services\CollectionBatchService;
use App\Services\PaymentMandateWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class QuickActionController extends Controller
{
    public function __construct(
        protected PaymentMandateWorkflowService $paymentMandateWorkflowService,
        protected CollectionBatchService $collectionBatchService,
        protected CollectionBatchPushService $collectionBatchPushService
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorizeQuickActions();

        return view('quick_actions.index', [
            'title' => 'Quick Actions',
        ]);
    }

    public function mandate(Request $request)
    {
        $this->authorizeQuickActions();

        $request->validate([
            'action' => 'required|in:cancellation,closing,archive',
            'mandate_references' => 'required|string',
            'remarks' => 'nullable|string|max:500',
        ]);

        $references = $this->references($request->input('mandate_references'));
        $results = [];

        if ($references->isEmpty()) {
            return redirect()
                ->route('quick-actions.index')
                ->with('danger', 'Enter at least one valid mandate reference.');
        }

        foreach ($references as $reference) {
            $debitOrder = DebitOrder::query()
                ->with('partner')
                ->where('reference', $reference)
                ->first();

            if (!$debitOrder) {
                $results[] = $this->mandateResult($reference, $request->action, false, 'Mandate reference was not found.');
                continue;
            }

            try {
                $results[] = match ($request->action) {
                    'cancellation' => $this->cancelMandate($debitOrder, (string) $request->input('remarks', 'Quick action mandate cancellation.')),
                    'closing' => $this->closeMandate($debitOrder),
                    'archive' => $this->archiveMandate($debitOrder),
                };
            } catch (ValidationException $exception) {
                $message = collect($exception->errors())->flatten()->first() ?: $exception->getMessage();
                $results[] = $this->mandateResult($reference, $request->action, false, $message, $debitOrder);
            } catch (Throwable $exception) {
                Log::error('Quick action mandate processing failed.', [
                    'mandate_reference' => $reference,
                    'action' => $request->action,
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]);

                $results[] = $this->mandateResult($reference, $request->action, false, $exception->getMessage(), $debitOrder);
            }
        }

        $successCount = collect($results)->where('success', true)->count();
        $message = $successCount === count($results)
            ? 'Quick mandate action completed successfully.'
            : "Quick mandate action completed with {$successCount} success(es) and " . (count($results) - $successCount) . ' failure(s).';

        return redirect()
            ->route('quick-actions.index')
            ->with($successCount === count($results) ? 'success' : 'warning', $message)
            ->with('quick_mandate_results', $results);
    }

    public function collectionBatch(Request $request)
    {
        $this->authorizeQuickActions();

        $request->validate([
            'collection_mandate_references' => 'required|string',
        ]);

        $references = $this->references($request->input('collection_mandate_references'));

        if ($references->isEmpty()) {
            return redirect()
                ->route('quick-actions.index')
                ->with('danger', 'Enter at least one valid mandate reference.');
        }
        $creation = $this->collectionBatchService->createBatchesByMandateReferences($references, Auth::user());
        $batches = $creation['created_batches'];
        $results = [];

        foreach ($batches as $batch) {
            $pushResult = $this->collectionBatchPushService->push($batch);
            $success = (bool) ($pushResult['success'] ?? false);

            $results[] = [
                'batch_reference' => $batch->collectionReference,
                'partner' => $batch->partner?->business_name,
                'collections' => $batch->number_of_collections,
                'collection_date' => $batch->collectionDate,
                'success' => $success,
                'message' => $pushResult['message'] ?? ($success ? 'Batch created and pushed.' : 'Batch created but push failed.'),
                'response_code' => $pushResult['response_code'] ?? null,
                'correlation_id' => $pushResult['correlation_id'] ?? null,
            ];
        }

        $successCount = collect($results)->where('success', true)->count();
        $failureCount = count($results) - $successCount;
        $flashType = $failureCount === 0 && $successCount > 0 ? 'success' : 'warning';
        $message = $successCount > 0
            ? "Created {$batches->count()} batch(es). Pushed {$successCount} successfully" . ($failureCount > 0 ? " and {$failureCount} failed." : '.')
            : 'No collection batch was created or pushed.';

        return redirect()
            ->route('quick-actions.index')
            ->with($flashType, $message)
            ->with('quick_collection_results', $results)
            ->with('quick_collection_messages', $creation['messages'] ?? [])
            ->with('quick_collection_skipped', $creation['skipped'] ?? []);
    }

    protected function cancelMandate(DebitOrder $debitOrder, string $remarks): array
    {
        if ($debitOrder->resolved_lifecycle_status === DebitOrder::LIFECYCLE_CANCELLED
            || (int) $debitOrder->cancelled === 1) {
            return $this->mandateResult(
                $debitOrder->reference,
                'cancellation',
                false,
                'Mandate is already cancelled.',
                $debitOrder
            );
        }

        $response = $this->paymentMandateWorkflowService->cancel($debitOrder, Auth::id(), $remarks);

        return $this->mandateResult(
            $debitOrder->reference,
            'cancellation',
            (bool) ($response['success'] ?? false),
            $response['success_msg'] ?? 'Cancellation processed.',
            $debitOrder->fresh(),
            $response['response_code'] ?? null,
            $response['provider_response_code'] ?? null
        );
    }

    protected function closeMandate(DebitOrder $debitOrder): array
    {
        $mandateRequest = $this->paymentMandateWorkflowService->requestClosing($debitOrder, Auth::id());
        $this->paymentMandateWorkflowService->approveClosing($mandateRequest, Auth::id());

        return $this->mandateResult($debitOrder->reference, 'closing', true, 'Mandate closed successfully.', $debitOrder->fresh());
    }

    protected function archiveMandate(DebitOrder $debitOrder): array
    {
        $mandateRequest = $this->paymentMandateWorkflowService->archive($debitOrder, Auth::id());
        $this->paymentMandateWorkflowService->approveArchive($mandateRequest, Auth::id());

        return $this->mandateResult($debitOrder->reference, 'archive', true, 'Mandate archived successfully.', $debitOrder->fresh());
    }

    protected function mandateResult(
        string $reference,
        string $action,
        bool $success,
        string $message,
        ?DebitOrder $debitOrder = null,
        mixed $responseCode = null,
        mixed $providerResponseCode = null
    ): array {
        return [
            'reference' => $reference,
            'action' => ucfirst($action),
            'partner' => $debitOrder?->partner?->business_name,
            'channel' => $debitOrder?->channel,
            'success' => $success,
            'message' => $message,
            'lifecycle_status' => $debitOrder?->fresh()?->lifecycle_status,
            'response_code' => $responseCode,
            'provider_response_code' => $providerResponseCode,
        ];
    }

    protected function references(string $value)
    {
        return collect(explode(',', $value))
            ->map(fn ($reference) => trim($reference))
            ->filter()
            ->unique()
            ->values();
    }

    protected function authorizeQuickActions(): void
    {
        $user = Auth::user();

        abort_unless(
            $user && collect($user->roles ?? [])
                ->pluck('name')
                ->intersect(['admin', 'Manager', 'manager'])
                ->isNotEmpty(),
            403
        );
    }
}
