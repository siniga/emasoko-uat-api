<?php

namespace App\Console\Commands;

use App\Models\PartnerAccount;
use App\Services\MnoBankServices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompanyCollectionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collection:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Status of on process collection';

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
        $partnerAccounts = PartnerAccount::where('send_verification', 2)->get();
        foreach ($partnerAccounts as $partnerAccount) {
            DB::beginTransaction();
            $mnoBank = new MnoBankServices($partnerAccount->account);
            $mnoBank->corporateRegistrationStatus(request(), $partnerAccount);
            DB::commit();
            sleep(15);
        }
        return 0;
    }
}
