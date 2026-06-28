<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MandateResource extends JsonResource
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
            'payment_channel' => $this->channel,

            'reference' => $this->reference,
            "periodicity" => $this->periodicity,
            "debit_type" => $this->debit_type,
            'installment_amount' => $this->installment_amount,
            'min_installment_amount' => $this->min_installment_amount,
            'max_installment_amount' => $this->max_installment_amount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'outstanding_amount' => $this->outstanding_amount,
            'number_of_installment' => $this->installment_number,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'lifecycle_status' => $this->lifecycle_status,
            'remarks' => $this->remarks,
        ];
    }
}
