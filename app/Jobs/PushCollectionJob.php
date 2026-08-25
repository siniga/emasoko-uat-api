<?php

namespace App\Jobs;

use App\Services\CollectionBatchPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushCollectionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $batchId;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
    }

    public function uniqueId(): string
    {
        return $this->batchId;
    }

    public function handle(CollectionBatchPushService $pushService): void
    {
        $pushService->pushById($this->batchId);
    }
}
