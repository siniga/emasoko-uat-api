<?php

namespace App\Http\Resources;

use App\Models\DebitOrder;
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
        $status = strtoupper((string) ($this->status ?? 'OPEN'));
        $debitOrder = $this->debitOrder;

        return [
            'id' => $this->id,
            'installment_order' => $this->installment_order,
            'mandate' => [
                'uuid' => $debitOrder->uuid ?? null,
                'reference' => $debitOrder->reference ?? null,
                'mandate_type' => $debitOrder ? $this->mandateType($debitOrder) : null,
                'mandate_type_label' => $debitOrder ? $this->mandateTypeLabel($debitOrder) : null,
                'payment_channel' => $debitOrder->channel ?? null,
                'debit_type' => $debitOrder->debit_type ?? null,
                'start_date' => $debitOrder->start_date ?? null,
                'end_date' => $debitOrder->end_date ?? null,
                'lifecycle_status' => $debitOrder?->resolved_lifecycle_status,
                'lifecycle_status_label' => $debitOrder
                    ? DebitOrder::lifecycleStatusLabel($debitOrder->resolved_lifecycle_status)
                    : null,
                'summary_status' => $debitOrder?->summary_status,
                'summary_status_label' => $debitOrder ? strtoupper($debitOrder->summary_status) : null,
            ],
            'service_provider' => $debitOrder && $debitOrder->partner ? [
                'uuid' => $debitOrder->partner->uuid,
                'corporate_reference' => $debitOrder->partner->reference,
                'name' => $debitOrder->partner->business_name,
            ] : null,
            'customer_account' => $debitOrder && $debitOrder->customerAccount ? [
                'name' => $debitOrder->customerAccount->account_name,
                'account_number' => $debitOrder->customerAccount->account_number,
                'phone' => $debitOrder->customerAccount->phone,
                'channel_name' => $debitOrder->customerAccount->account->name ?? null,
            ] : null,
            'installment_amount' => $this->installment_amount,
            'current_balance' => $this->current_balance,
            'outstanding_amount' => $this->outstanding_amount,
            'last_paid_amount' => $this->last_paid_amount,
            'payment_date' => $this->payment_date,
            'effective_payment_date' => $this->effective_payment_date,
            'reference' => $this->payment_reference,
            'status' => $status,
            'status_label' => $this->collectionStatusLabel($status),
            'remarks' => $this->remarks,
        ];
    }

    protected function collectionStatusLabel(string $status): string
    {
        return match ($status) {
            'CLOSED' => 'FULLY PAID',
            'PARTIAL_PAID' => 'PARTIAL PAID',
            'FUNDSCHECK' => 'FUNDSCHECK',
            default => ucwords(strtolower(str_replace('_', ' ', $status))),
        };
    }

    protected function mandateType(DebitOrder $debitOrder): string
    {
        $type = $debitOrder->getAttribute('mandate_type')
            ?: $debitOrder->getAttribute('mandate_service')
            ?: $debitOrder->getAttribute('service_type')
            ?: 'NORMAL';

        return strtoupper((string) $type);
    }

    protected function mandateTypeLabel(DebitOrder $debitOrder): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $this->mandateType($debitOrder))));
    }
}
