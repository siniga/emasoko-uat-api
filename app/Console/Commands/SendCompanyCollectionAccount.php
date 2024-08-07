<?php

namespace App\Console\Commands;

use App\Models\DebitOrder;
use App\Models\Partner;
use App\Models\PartnerAccount;
use App\Services\MnoBankServices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendCompanyCollectionAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send All To be varified companies collection account';

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
        Log::debug("Command excuted");
        // $debits = DebitOrder::whereHas("partner", function ($query) {
        //     return $query->where("business_name", "NHC");
        // })->active()->get();
        // foreach ($debits as $debit) {
        //     DB::beginTransaction();
        //     $mnoBank = new MnoBankServices($debit->customerAccount->account);
        //     $res = $mnoBank->mandateRegistration(request(), $debit);
        //     dd($res);
        //     DB::commit();
        // }
        // $partnerAccounts = PartnerAccount::where('send_verification', 1)->get();
        // foreach ($partnerAccounts as $partnerAccount) {
        //     DB::beginTransaction();
        //     $mnoBank = new MnoBankServices($partnerAccount->account);
        //     $registration = $mnoBank->corporateRegistration(request(), $partnerAccount);
        //     if ($registration->status) {
        //         sleep(10);
        //         $mnoBank->corporateRegistrationStatus(request(), $partnerAccount);
        //     }
        //     DB::commit();
        //     sleep(15);
        // }
        // return 0;
    }
}
