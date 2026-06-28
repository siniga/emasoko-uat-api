<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'mandate' => new MandateResource($this->whenLoaded('debitOrder')),
            'mandate' => [
                "reference" => $this->debitOrder->reference ?? null,
                "debit_type" => $this->debitOrder->debit_type ?? null,
                'start_date' => $this->debitOrder->start_date,
                'end_date' => $this->debitOrder->end_date,
                'lifecycle_status' => $this->debitOrder->lifecycle_status,
            ],
            "installment_amount" => $this->installment_amount,
            "current_balance" => $this->current_balance,
            "outstanding_amount" => $this->outstanding_amount,
            "last_paid_amount" => $this->last_paid_amount,
            "payment_date" => $this->payment_date,
            "effective_payment_date" => $this->effective_payment_date,
            "reference" => $this->payment_reference,
            "status" => match ($this->status) {
                'CLOSED' => 'PAID',
                'OPEN' => 'PENDING',
                default => $this->status,
            },
            "remarks" => $this->remarks,
        ];
    }
}
