<?php

namespace Database\Seeders\Testing;

use App\Models\DebitOrder;
use App\Models\User;
use Illuminate\Database\Seeder;

class ObservabilityDataSeeder extends Seeder
{
    public function run(TestingDataContext $data): void
    {
        for ($i=1;$i<=$data->count;$i++) {
            $user = $data->get('users','operator');
            $mandate = $data->get('debit_orders',$i);
            $batch = $data->get('collection_batches',$i);
            $paid = $data->paid($i);
            $data->row('audit_logs',['uuid'=>TestingDataContext::uuid('audit.'.$i)],[
                'event'=>'created','auditable_type'=>DebitOrder::class,'auditable_id'=>(string)$mandate->id,
                'user_id'=>$user->id,'user_name'=>$user->name,'user_email'=>$user->email,'guard'=>'web',
                'request_method'=>'POST','route_name'=>'testing.fixture','url'=>'https://testing.emakato.invalid/fixtures',
                'ip_address'=>'127.0.0.1','user_agent'=>'TestingDataSeeder','new_values'=>json_encode(['reference'=>$mandate->reference]),
                'metadata'=>json_encode(['synthetic'=>true]),
            ]);
            $data->row('back_logs',['action'=>'testing-fixture','source'=>$data->ref('BACK',$i)],[
                'status'=>'SUCCESS','message'=>'Synthetic batch history','loggable_type'=>\App\Models\CollectionBatch::class,'loggable_id'=>$batch->id,
            ]);
            $data->row('notifications',['id'=>TestingDataContext::uuid('notification.'.$i)],[
                'type'=>'testing.fixture','notifiable_type'=>User::class,'notifiable_id'=>$data->id('users',$i),
                'data'=>json_encode(['synthetic'=>true,'message'=>'Testing mandate '.$mandate->reference,'debit_order_id'=>$mandate->id]),
                'read_at'=>$data->date,
            ]);
            $data->row('sms_logs',['uuid'=>TestingDataContext::uuid('sms-log.'.$i)],[
                'message_id'=>$data->ref('SMS',$i),'debit_order_id'=>(string)$mandate->id,
                'message'=>'Synthetic history for '.$mandate->reference.'. No message was sent.',
                'number_of_sms'=>1,'phone'=>$data->get('customers',$i)->phone,'sender'=>'TESTING',
                'status'=>$paid>0?'DELIVERED':'FAILED','schedule'=>0,'sent_at'=>$data->date,
                'delivered_at'=>$paid>0?$data->date:null,'response'=>json_encode(['synthetic'=>true]),
                'created_by'=>$user->id,'updated_by'=>$user->id,
            ]);
            $data->row('external_callback_logs',['event_id'=>TestingDataContext::uuid('callback.'.$i)],[
                'partner_id'=>$data->id('partners',$i),'debit_order_id'=>$mandate->id,'callback_type'=>'MANDATE',
                'event_type'=>'testing.fixture','callback_url'=>'https://testing.emakato.invalid/callback',
                'payload'=>json_encode(['synthetic'=>true,'reference'=>$mandate->reference]),'status'=>'DELIVERED',
                'attempts'=>1,'last_response_code'=>200,'response_body'=>json_encode(['synthetic'=>true]),
                'last_attempt_at'=>$data->date,'delivered_at'=>$data->date,
            ]);
            $data->row('collection_integrity_issues',['fingerprint'=>hash('sha256','testing.resolved.'.$i)],[
                'audit_run_id'=>TestingDataContext::uuid('integrity-run.'.$i),'issue_type'=>'TESTING_RESOLVED_EXAMPLE','severity'=>'INFO',
                'settlement_id'=>$data->id('payment_settlements',$i),'debit_order_id'=>$mandate->id,
                'installment_id'=>$data->id('installments',$i),'mandate_reference'=>$mandate->reference,
                'payment_reference'=>$data->ref('PAY',$i),'amount'=>0,'event_date'=>$data->date->toDateString(),
                'description'=>'Resolved synthetic example for testing the integrity history screen',
                'context'=>json_encode(['synthetic'=>true]),'detected_at'=>$data->date,'last_detected_at'=>$data->date,
                'resolved_at'=>$data->date,'resolution_note'=>'Testing fixture, not a live integrity error',
            ]);
            $data->row('system_health_checks',['endpoint_key'=>$data->ref('HEALTH',$i),'checked_at'=>$data->date->format('Y-m-d H:i:s')],[
                'service'=>$data->channel($i),'endpoint_label'=>'Synthetic testing check '.$i,'check_type'=>'tcp_tls',
                'status'=>'healthy','response_time_ms'=>10+$i,'port'=>443,'message'=>'Synthetic historical check; no connection was made',
            ]);
        }
    }
}
