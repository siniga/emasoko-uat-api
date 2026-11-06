<?php

namespace Database\Seeders\Testing;

use App\Models\CollectionDebitOrder;
use Illuminate\Database\Seeder;

class CollectionDataSeeder extends Seeder
{
    public function run(TestingDataContext $data): void
    {
        for ($i=1;$i<=$data->count;$i++) {
            $scenario = $data->scenario($i);
            $complete = $scenario!==3;
            $success = in_array($scenario,[0,1]);
            $status = $data->paymentStatus($i);
            $batch = $data->row('collection_batches',['collectionReference'=>$data->ref('BATCH',$i)],[
                'id'=>TestingDataContext::uuid('batch.'.$i),'partner_id'=>$data->id('partners',$i),
                'bank_sponsor_partner_id'=>$data->id('partners',$data->sponsor($i)),'bank_sponsor_source'=>'testing',
                'corporateReference'=>$data->get('partners',$i)->reference,'selectedPostingDate'=>$data->date->toDateString(),
                'collectionDate'=>$data->date->toDateString(),'createdBy'=>(string)$data->id('users','operator'),
                'verifiedBy'=>$complete?(string)$data->id('users','approver'):null,'createdDate'=>$data->date,'verifyDate'=>$complete?$data->date:null,
                'pushed'=>$complete?1:0,'processed'=>$complete?1:0,
                'lifecycle_status'=>$complete?($success?'processed_success':'processed_failed'):'draft',
                'last_pushed_at'=>$complete?$data->date:null,'last_status_checked_at'=>$complete?$data->date:null,
                'push_correlation_id'=>TestingDataContext::uuid('batch-correlation.'.$i),'status'=>$complete?($success?230:235):100,
                'failed'=>$complete&&!$success?1:0,'number_of_collections'=>1,'number_of_success'=>$success?1:0,
                'number_of_failed'=>$complete&&!$success?1:0,'installment_ids'=>json_encode([$data->id('installments',$i)]),
                'remarks'=>'Synthetic testing batch','deleted'=>0,'recreated'=>0,
                'response'=>json_encode(['synthetic'=>true,'status'=>$status]),
            ]);
            $data->remember('collection_batches',$i,$batch);
            $account = $data->get('customer_accounts',$i)->account_number;
            $line = $data->row('collection_debit_orders',['installment_id'=>$data->id('installments',$i),'payment_at'=>$data->date->toDateString().' 00:00:00'],[
                'collection_batch_id'=>$batch->id,'debit_order_id'=>$data->id('debit_orders',$i),'account_number'=>$account,
                'status'=>$complete?$status:'CREATED','bank_status'=>$complete?$status:'CREATED','payment_month'=>$data->date->format('Y-m'),
                'account_month_unique_key'=>CollectionDebitOrder::accountMonthUniqueKey($account,$data->date,(int)$data->id('partners',$i)),
                'pushed_at'=>$complete?$data->date:null,'processed_at'=>$complete?$data->date:null,'bank_message'=>'Synthetic testing result',
            ]);
            $data->remember('collection_debit_orders',$i,$line);
            $data->row('collection_batch_push_attempts',['id'=>TestingDataContext::uuid('push-attempt.'.$i)],[
                'collection_batch_id'=>$batch->id,'correlation_id'=>TestingDataContext::uuid('batch-correlation.'.$i),
                'status'=>$complete?($success?'SUCCESS':'FAILED'):'SKIPPED','http_status'=>$complete?200:null,
                'response_code'=>'TEST','message'=>'Synthetic history; no bank request was sent',
            ]);
        }
    }
}
