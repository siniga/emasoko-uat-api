<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;

class CustomerDataSeeder extends Seeder
{
    public function run(TestingDataContext $data): void
    {
        for ($i=1;$i<=$data->count;$i++) {
            $partner = $data->get('partners',$i);
            $customer = $data->row('customers',['email'=>'customer'.$i.'@testing.emakato.invalid'],[
                'fullname'=>'Test Customer '.sprintf('%03d',$i),'phone'=>$data->phone(400+$i),'dob'=>'1990-01-01',
                'id_type'=>'NIN','id_number'=>$data->ref('NIN',$i),'address'=>'Testing address '.$i,
                'status'=>'ACTIVE','remarks'=>'Synthetic testing customer','created_by'=>$data->id('users','operator'),'updated_by'=>$data->id('users','operator'),
            ]);
            $data->remember('customers',$i,$customer);
            $account = $data->row('customer_accounts',['uuid'=>TestingDataContext::uuid('customer-account.'.$i)],[
                'customer_id'=>$customer->id,'account_type_id'=>1,'account_id'=>$data->accountId($i),
                'account_number'=>'TESTCA'.sprintf('%05d',$i),'account_name'=>$customer->fullname,'bank_code'=>$i%2?'016':'015',
                'phone'=>$customer->phone,'status'=>'ACTIVE','remarks'=>'Synthetic account; use sandbox details for bank tests',
                'created_by'=>$data->id('users','operator'),'updated_by'=>$data->id('users','operator'),
            ]);
            $data->remember('customer_accounts',$i,$account);
            $data->row('customer_partners',['customer_id'=>$customer->id,'partner_id'=>$partner->id],[
                'created_by'=>$data->id('users','operator'),'updated_by'=>$data->id('users','operator'),
            ]);
            $subscriber = $data->row('subscribers',['subscriber_reference'=>$data->ref('SUB',$i)],[
                'id'=>TestingDataContext::uuid('subscriber.'.$i),'partner_id'=>(string)$partner->id,
                'external_reference'=>$data->ref('EXT',$i),'name'=>$customer->fullname,'phone'=>$customer->phone,'email'=>$customer->email,
                'identity_type'=>'NIN','identity_number'=>$customer->id_number,'channel'=>$data->channel($i),
                'account_id'=>$account->account_id,'account_type_id'=>1,'account_name'=>$account->account_name,'account_number'=>$account->account_number,
                'bank_code'=>$account->bank_code,'branch_code'=>'001','status'=>'active','registration_status'=>'registered','registration_source'=>'web',
                'registration_payload'=>json_encode(['synthetic'=>true]),'registration_response'=>json_encode(['synthetic'=>true,'status'=>'registered']),
                'registered_at'=>$data->date,'submitted_at'=>$data->date,'created_by'=>$data->id('users','operator'),
                'approved_by'=>$data->id('users','approver'),'approved_at'=>$data->date,'deleted'=>0,
            ]);
            $data->remember('subscribers',$i,$subscriber);
            foreach (['subscriber.admin','subscriber.user'] as $profile) $data->row('subscriber_users',[
                'user_id'=>$data->id('users',$profile),'partner_id'=>(string)$partner->id,'subscriber_id'=>$subscriber->id,
            ],['authorize'=>1]);
        }
    }
}
