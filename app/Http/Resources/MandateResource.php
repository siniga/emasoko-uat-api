<?php

namespace App\Http\Resources;

use App\Models\DebitOrder;
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
            'mandate_type' => $this->mandateType(),
            'mandate_type_label' => $this->mandateTypeLabel(),
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
            'lifecycle_status' => $this->resolved_lifecycle_status,
            'lifecycle_status_label' => DebitOrder::lifecycleStatusLabel($this->resolved_lifecycle_status),
            'summary_status' => $this->summary_status,
            'summary_status_label' => strtoupper($this->summary_status),
            'contract_status' => $this->contract_status,
            'remarks' => $this->remarks_message,
        ];
    }

    protected function mandateType(): string
    {
        $type = $this->getAttribute('mandate_type')
            ?: $this->getAttribute('mandate_service')
            ?: $this->getAttribute('service_type')
            ?: 'NORMAL';

        return strtoupper((string) $type);
    }

    protected function mandateTypeLabel(): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $this->mandateType())));
    }
}
