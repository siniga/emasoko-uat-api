<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;

class PaymentDataSeeder extends Seeder
{
    public function run(TestingDataContext $data): void
    {
        for ($i=1;$i<=$data->count;$i++) {
            $amount = $data->amount($i);
            $paid = $data->paid($i);
            $scenario = $data->scenario($i);
            $status = $data->paymentStatus($i);
            $mandate = $data->get('debit_orders',$i);
            $reference = $data->ref('PAY',$i);
            $run = TestingDataContext::uuid('settlement-backfill.'.$i);
            $settlement = $data->row('payment_settlements',['payment_reference'=>$reference,'channel'=>$data->channel($i)],[
                'collection_batch_id'=>$data->id('collection_batches',$i),'collection_debit_order_id'=>$data->id('collection_debit_orders',$i),
                'debit_order_id'=>$mandate->id,'installment_id'=>$data->id('installments',$i),'mandate_reference'=>$mandate->reference,
                'external_reference'=>$data->ref('EXT',$i),'raw_status'=>$status,'normalized_status'=>$status,
                'settlement_type'=>match($scenario) {0=>'FULL_SETTLEMENT',1=>'PARTIAL_SETTLEMENT',default=>'FAILED_COLLECTION'},
                'applied_to_balances'=>$scenario===3?0:1,'reconciliation_status'=>$scenario===3?'UNALLOCATED':'POSTED',
                'backfill_run_id'=>$run,'expected_amount'=>$amount,'paid_amount'=>$paid,
                'opening_installment_balance'=>$amount,'closing_installment_balance'=>$amount-$paid,
                'opening_debit_order_balance'=>$amount,'closing_debit_order_balance'=>$amount-$paid,
                'effective_payment_date'=>$scenario===3?null:$data->date->toDateString(),'transaction_at'=>$data->date,
                'remarks'=>'Synthetic testing settlement','raw_payload_hash'=>hash('sha256',$reference),
            ]);
            $data->remember('payment_settlements',$i,$settlement);
            if ($paid>0) {
                $data->row('installment_payments',['payment_reference'=>$reference,'installment_id'=>$data->id('installments',$i)],[
                    'mandate_reference'=>$mandate->reference,'amount'=>$paid,'payment_at'=>$data->date,
                ]);
                $data->row('payment_histories',['debit_order_id'=>$mandate->id,'payment_reference'=>$reference],[
                    'expected_amount'=>$amount,'paid_amount'=>$paid,
                ]);
                $data->row('payments',['reference'=>$reference],[
                    'amount'=>$paid,'msisdn'=>$data->get('customers',$i)->phone,'status'=>'SUCCESS',
                    'transid'=>$data->ref('TRANS',$i),'insight_reference'=>$data->ref('INSIGHT',$i),'remarks'=>'Synthetic testing payment',
                ]);
            }
            $data->row('payment_logs',['reference'=>$data->ref('LOG',$i)],[
                'msisdn'=>$data->get('customers',$i)->phone,'amount'=>$amount,'end_date'=>$data->date->toDateString(),
                'request_id'=>$data->ref('REQ',$i),'status'=>$paid>0?'SUCCESS':($scenario===3?'INITIATED':'FAIL'),
                'remarks'=>'Synthetic testing payment log',
            ]);
            $data->row('payment_settlement_backfill_archives',['run_id'=>$run,'payment_settlement_id'=>$settlement->id],[
                'debit_order_id'=>$mandate->id,'settlement_payload'=>json_encode($settlement),
                'archived_at'=>$data->date->subDay(),'restored_at'=>$data->date,
            ]);
            if ($i%3===2) {
                $data->row('gepg_bill_payments',['idempotency_key'=>$data->ref('GEPG-PAY',$i)],[
                    'debit_order_id'=>$mandate->id,'payment_settlement_id'=>$settlement->id,'mandate_reference'=>$mandate->reference,
                    'control_number'=>$mandate->control_number,'trigger'=>'ON_COLLECTION','request_id'=>$data->ref('GEPG-REQ',$i),
                    'amount'=>$paid,'currency'=>'TZS','status'=>$paid>0?'SUCCESS':'FAILED','response_code'=>'TEST',
                    'message'=>'Synthetic GEPG history; no request sent','request_payload'=>json_encode(['synthetic'=>true]),
                    'response_payload'=>json_encode(['synthetic'=>true,'status'=>$status]),'sent_at'=>$data->date,
                ]);
            }
            if ($i%3===0) {
                $purchase = $data->row('luku_payment_purchases',['uuid'=>TestingDataContext::uuid('luku-purchase.'.$i)],[
                    'partner_id'=>(string)$data->id('partners',$i),'subscriber_id'=>$data->id('subscribers',$i),
                    'meter_number'=>$mandate->luku_meter_number,'payer_name'=>$data->get('customers',$i)->fullname,
                    'payer_mobile_number'=>$data->get('customers',$i)->phone,'amount'=>$amount,'currency'=>'TZS',
                    'request_id'=>$data->ref('LUKU-REQ',$i),'status'=>$paid>0?'SUCCESS':'FAILED','response_code'=>'TEST',
                    'message'=>'Synthetic LUKU history; no purchase made','request_payload'=>json_encode(['synthetic'=>true]),
                    'response_payload'=>json_encode(['synthetic'=>true,'status'=>$status]),
                    'created_by'=>$data->id('users','operator'),'debit_order_id'=>$mandate->id,'sent_at'=>$data->date,
                ]);
                // Resolve this circular link only for a mandate inserted by this run.
                if (isset($data->inserted['debit_orders'][(string)$mandate->id])) {
                    $data->db->table('debit_orders')->where('id',$mandate->id)->update(['luku_payment_purchase_id'=>$purchase->id]);
                    $mandate->luku_payment_purchase_id = $purchase->id;
                }
            }
            if ($i%3!==1) {
                $data->row('subscriber_mandate_requests',['id'=>TestingDataContext::uuid('subscriber-request.'.$i)],[
                    'subscriber_id'=>$data->id('subscribers',$i),'partner_id'=>(string)$data->id('partners',$i),
                    'debit_order_id'=>$mandate->id,'control_number'=>$mandate->control_number ?? $mandate->luku_meter_number,
                    'control_amount'=>$amount,'charge_percent'=>0,'charge_amount'=>0,'total_amount'=>$amount,
                    'currency'=>'TZS','settlement_trigger'=>'ON_COLLECTION','status'=>$scenario===3?'draft':'verified',
                    'verification_payload'=>json_encode(['synthetic'=>true]),'verification_response'=>json_encode(['synthetic'=>true,'verified'=>true]),
                    'remarks'=>'Synthetic subscriber mandate request','created_by'=>$data->id('users','operator'),
                ]);
            }
        }
    }
}
