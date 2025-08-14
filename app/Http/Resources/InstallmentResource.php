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
                'contract_status' => $this->debitOrder->contract_status,
                'approved' => $this->debitOrder->approved
            ],
            "installment_order" => $this->installment_order,
            "installment_amount" => $this->installment_amount,
            "min_installment_amount" => $this->min_installment_amount,
            "max_installment_amount" => $this->max_installment_amount,
            "current_balance" => $this->current_balance,
            "outstanding_amount" => $this->outstanding_amount,
            "payment_date" => $this->payment_date,
            "last_paid_amount" => $this->last_paid_amount,
            "reference" => $this->payment_reference,
            "status" => $this->status,
            "remarks" => $this->remarks,
        ];
    }
}
