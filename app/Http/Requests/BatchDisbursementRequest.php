<?php

namespace App\Http\Requests;

class BatchDisbursementRequest
{
    /** @param BatchRecord[] $records */
    public function __construct(
        public string $batchID,
        public string $batchAccount,
        public string $batchSender,
        public string $batchDesc,
        public float  $batchTotalAmount,
        public array  $records,
        public string $batchCurrency = 'TZS',
        public string $batchPostType = 'S',   // S or M :contentReference[oaicite:12]{index=12}
        public string $batchApproval = 'N',   // Y or N :contentReference[oaicite:13]{index=13}
        public string $batchCode = 'CRDBBTDIS' // Disbursement :contentReference[oaicite:14]{index=14}
    ) {}

    public function toArray(string $sessionToken, string $partnerID): array
    {
        return [
            'batch' => [
                'batchCode'        => $this->batchCode,
                'batchPostType'    => $this->batchPostType,
                'batchApproval'    => $this->batchApproval,
                'batchID'          => $this->batchID,
                'batchAccount'     => $this->batchAccount,
                'batchSender'      => $this->batchSender,
                'batchDesc'        => $this->batchDesc,
                'batchCurrency'    => $this->batchCurrency,
                'batchTotalAmount' => $this->batchTotalAmount,
            ],
            'records'      => array_map(fn($r) => $r->toArray(), $this->records),
            'sessionToken' => $sessionToken,
            'partnerID'    => $partnerID,
        ];
    }
}
