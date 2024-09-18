<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CollectionStatusBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collection:query-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query Collection Status Batch';

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
        return 0;
    }
}
