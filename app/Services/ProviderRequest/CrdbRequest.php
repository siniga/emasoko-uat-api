<?php

namespace App\Services\ProviderRequest;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CrdbRequest extends BankMnoProcedure
{
    public function __construct()
    {
        parent::__construct();
    }
}
