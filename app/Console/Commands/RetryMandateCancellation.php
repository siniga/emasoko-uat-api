<?php

namespace App\Console\Commands;

use App\Models\DebitOrder;
use App\Services\MandateActionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryMandateCancellation extends Command
{
    protected $signature = 'mandates:retry-cancellation
        {--references= : Comma-separated mandate references}
        {--send : Send cancellation requests to the banks}';

    protected $description = 'Retry bank cancellation for locally cancelled mandates and reconcile the resulting status';

    public function __construct(protected MandateActionService $mandateActionService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $references = collect(explode(',', (string) $this->option('references')))
            ->map(fn ($reference) => trim($reference))
            ->filter()
            ->unique()
            ->values();

        if ($references->isEmpty()) {
            $this->error('Pass mandate references using --references=MANDATE-001,MANDATE-002.');

            return self::FAILURE;
        }

        $mandates = DebitOrder::query()
            ->whereIn('reference', $references)
            ->get()
            ->keyBy('reference');

        $references->diff($mandates->keys())->each(
            fn ($reference) => $this->warn("Mandate {$reference} was not found.")
        );

        if (!$this->option('send')) {
            $this->table(
                ['Reference', 'Channel', 'Lifecycle', 'Cancelled', 'Eligible'],
                $references->map(function ($reference) use ($mandates) {
                    $mandate = $mandates->get($reference);

                    return [
                        $reference,
                        $mandate?->channel ?? '-',
                        $mandate?->lifecycle_status ?? '-',
                        $mandate?->cancelled ?? '-',
                        $mandate ? 'YES' : 'NO',
                    ];
                })->all()
            );

            $this->info('Dry run only. Add --send to contact the banks.');

            return self::SUCCESS;
        }

        $successful = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($references as $reference) {
            /** @var DebitOrder|null $mandate */
            $mandate = $mandates->get($reference);

            if (!$mandate) {
                $skipped++;
                continue;
            }

            $mandate->cancelled = 0;
            $mandate->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED;
            $mandate->save();
            $mandate->refresh();

            try {
                $response = $this->mandateActionService->cancelMandate($mandate);
            } catch (\Throwable $exception) {
                $mandate->cancelled = 0;
                $mandate->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
                $mandate->remarks = 'Cancellation retry failed: ' . $exception->getMessage();
                $mandate->save();

                Log::error('Mandate cancellation retry threw an exception.', [
                    'debit_order_id' => $mandate->id,
                    'mandate_reference' => $mandate->reference,
                    'channel' => $mandate->channel,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                $this->error("{$reference}: {$exception->getMessage()}");
                $failed++;
                continue;
            }

            if ($response['success']) {
                $mandate->cancelled = 1;
                $mandate->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLED;
                $mandate->remarks = $response['remarks'];
                $mandate->save();

                $this->info("{$reference}: cancellation confirmed ({$response['remarks']}).");
                $successful++;
            } else {
                $mandate->cancelled = 0;
                $mandate->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
                $mandate->remarks = $response['remarks'];
                $mandate->save();

                $providerCode = $response['provider_response_code'] ?? 'N/A';
                $this->error("{$reference}: cancellation not confirmed [{$providerCode}] {$response['remarks']}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Completed: {$successful} confirmed, {$failed} failed, {$skipped} skipped.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
