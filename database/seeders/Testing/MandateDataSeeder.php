<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;

class MandateDataSeeder extends Seeder
{
    public function run(TestingDataContext $data): void
    {
        for ($i=1;$i<=$data->count;$i++) {
            $scenario = $data->scenario($i);
            $amount = $data->amount($i);
            $paid = $data->paid($i);
            $state = ['closed','active','active','draft','cancelled'][$scenario];
            $otp = $data->row('otp_requests',['reference'=>$data->ref('OTP',$i)],[
                'account_number'=>$data->get('customer_accounts',$i)->account_number,'account_name'=>$data->get('customers',$i)->fullname,
                'otp_pin'=>'000000','action'=>'VERIFIED','created_by'=>$data->id('users','operator'),
                'http_response_code'=>200,'http_response_json'=>json_encode(['synthetic'=>true,'verified'=>true]),
            ]);
            $mandate = $data->row('debit_orders',['reference'=>$data->ref('MD',$i)],[
                'uuid'=>TestingDataContext::uuid('mandate.'.$i),'partner_id'=>$data->id('partners',$i),
                'bank_sponsor_partner_id'=>$data->id('partners',$data->sponsor($i)),
                'bank_sponsor_partner_account_id'=>$data->id('partner_accounts',$data->sponsor($i)),
                'bank_sponsor_source'=>'testing','subscriber_id'=>$data->id('subscribers',$i),
                'mandate_source'=>['NORMAL','GEPG_SUBSCRIBER','LUKU_PAYMENT'][($i-1)%3],
                'control_number'=>$i%3===2?$data->ref('GEPG',$i):null,
                'control_number_amount'=>$i%3===2?$amount:null,'charge_amount'=>0,
                'luku_meter_number'=>$i%3===0?$data->ref('METER',$i):null,'settlement_trigger'=>'ON_COLLECTION',
                'customer_account_id'=>$data->id('customer_accounts',$i),'total_amount'=>$amount,'paid_amount'=>$paid,
                'outstanding_amount'=>$amount-$paid,'current_balance'=>$amount-$paid,'debit_type'=>'FIXED',
                'installment_amount'=>$amount,'min_installment_amount'=>$amount,'max_installment_amount'=>$amount,
                'installment_number'=>1,'paid_installment'=>$paid===$amount?1:0,'failed_installment'=>$scenario===2?1:0,
                'periodicity'=>'MONTHLY','deduction_end_date'=>'totalLoanCovered','start_date'=>$data->date->toDateString(),
                'end_date'=>$data->date->addYear()->toDateString(),'aggrement_evidence'=>'emakato-testing-sample.txt',
                'contract_status'=>in_array($scenario,[0,4])?'CLOSED':'OPEN','status'=>['SUCCESS','SUCCESS','FAILED','PENDING','CANCELLED'][$scenario],
                'approved'=>$scenario===3?'0':'1','approved_by'=>$scenario===3?null:$data->id('users','approver'),
                'approver_at'=>$scenario===3?null:$data->date,'remarks'=>'Synthetic testing mandate: '.$state,
                'created_by'=>$data->id('users','operator'),'cancelled'=>$scenario===4?1:0,'closed'=>$scenario===0?1:0,
                'archived'=>0,'deleted'=>0,'lifecycle_status'=>$state,'channel'=>$data->channel($i),
                'otp_reference'=>$otp->reference,'request_id'=>$data->ref('REQ',$i),
                'next_debit_date'=>in_array($scenario,[1,2])?now()->addDays(7)->toDateString():null,
                'cancellationStartedBy'=>$scenario===4?$data->id('users','operator'):null,
                'cancellationApprovedBy'=>$scenario===4?$data->id('users','approver'):null,
                'cancellationStartedAt'=>$scenario===4?$data->date:null,'cancellationApprovedAt'=>$scenario===4?$data->date:null,
                'cancellation_reason'=>$scenario===4?'Testing cancellation':null,
            ]);
            $data->remember('debit_orders',$i,$mandate);
            $data->remember('installments',$i,$data->row('installments',['debit_order_id'=>$mandate->id,'installment_order'=>1],[
                'installment_amount'=>$amount,'min_installment_amount'=>$amount,'max_installment_amount'=>$amount,
                'current_balance'=>$amount-$paid,'outstanding_amount'=>$amount-$paid,'last_paid_amount'=>$paid,
                'past_due_amount'=>0,'due_days'=>0,'payment_type'=>'FIXED','payment_reference'=>$paid?$data->ref('PAY',$i):null,
                'payment_date'=>$data->date->toDateString(),'effective_payment_date'=>$paid?$data->date->toDateString():null,
                'status'=>$paid===$amount?'PAID':($paid?'PARTIAL':'OPEN'),'remarks'=>'Synthetic testing installment','pushed'=>$scenario===3?0:1,'created'=>1,
            ]));
            $data->row('debit_order_requests',['debit_order_id'=>$mandate->id,'request_type'=>'CANCELLATION'],[
                'status'=>$scenario===4?'APPROVED':'REJECTED','requested_by'=>$data->id('users','operator'),'requested_at'=>$data->date,
                'approved_by'=>$scenario===4?$data->id('users','approver'):null,'approved_at'=>$scenario===4?$data->date:null,
                'rejected_by'=>$scenario===4?null:$data->id('users','approver'),'rejected_at'=>$scenario===4?null:$data->date,
            ]);
        }
    }
}
