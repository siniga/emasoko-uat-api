<?php

namespace App\Jobs;

use App\Services\MessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSMSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;
    private MessagingService $messaging;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->messaging = new MessagingService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //call messaging class
        $response = $this->messaging->actionSendSMS($this->data['messageId']);
        Log::debug("== Sending SMS in background ==");
        Log::debug($response);
    }
}
