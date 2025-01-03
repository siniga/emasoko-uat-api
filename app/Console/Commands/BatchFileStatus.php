<?php

namespace App\Console\Commands;

use App\Classes\Messaging;
use App\Models\CollectionBatch;
use App\Services\MnoBankServices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BatchFileStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //call messaging class
        Log::debug("== Sending SMS in command ==");
        return 0;
    }
}
