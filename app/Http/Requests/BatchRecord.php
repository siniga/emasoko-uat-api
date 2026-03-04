<?php

namespace App\Http\Requests;

class BatchRecord
{
    public function __construct(
        public string $recID,
        public string $recAccount,
        public string $recBic,
        public string $recName,
        public string $recRef,
        public string $recSecRef,
        public float  $recAmount,
        public string $recCurrency = 'TZS',
        public string $recDesc = 'Mandate Disbursement',
    ) {}

    public function toArray(): array
    {
        return [
            'recID'       => $this->recID,
            'recAccount'  => $this->recAccount,
            'recBic'      => $this->recBic,
            'recName'     => $this->recName,
            'recRef'      => $this->recRef,
            'recSecRef'   => $this->recSecRef,
            'recAmount'   => $this->recAmount,
            'recCurrency' => $this->recCurrency,
            'recDesc'     => $this->recDesc,
        ];
    }
}
