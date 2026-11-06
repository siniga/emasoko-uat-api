<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;

class PartnerDataSeeder extends Seeder
{
    public function run(TestingDataContext $data): void
    {
        for ($i=1;$i<=$data->count;$i++) {
            $partner = $data->row('partners',['uuid'=>TestingDataContext::uuid('partner.'.$i)],[
                'partner_category_id'=>$i<=2?1:1+(($i-1)%5),'business_name'=>'Test Service Provider '.sprintf('%03d',$i),
                'year_of_incorporation'=>2020,'tin_number'=>$data->ref('TIN',$i),'number_of_staff'=>3,
                'region_id'=>(string)(1+(($i-1)%31)),'phone'=>$data->phone(200+$i),'email'=>'provider'.$i.'@testing.emakato.invalid',
                'website'=>'https://testing.emakato.invalid','physical_address'=>'Testing address '.$i.', Dar es Salaam',
                'postal_address'=>'Testing address '.$i,'complete'=>1,'approved'=>1,'status'=>'active','lifecycle_status'=>'active',
                'registration_source'=>'officer','next_step'=>null,'created_by'=>$data->id('users','admin'),'updated_by'=>$data->id('users','admin'),
                'reference'=>$data->ref('CO',$i),'short_code'=>'T'.str_pad((string)$i,3,'0',STR_PAD_LEFT),
                'client_code'=>'T'.str_pad((string)$i,3,'0',STR_PAD_LEFT),'company_id'=>$data->id('companies',1+(($i-1)%3)),
                'remarks'=>'Synthetic testing provider','callback_url'=>null,'callback_secret'=>null,
            ]);
            $data->remember('partners',$i,$partner);
            $data->remember('partner_accounts',$i,$data->row('partner_accounts',['partner_id'=>$partner->id,'account_number'=>'TESTPA'.sprintf('%05d',$i)],[
                'partner_uuid'=>$partner->uuid,'account_type_id'=>1,'account_id'=>$data->accountId($i),
                'account_name'=>$partner->business_name,'status'=>1,'onboarding_status'=>'active','is_verified'=>1,
                'verified_at'=>$data->date,'send_verification'=>0,'collection_account_source'=>'own',
                'currency_id'=>$data->id('currencies','5902315d-bd37-4dbb-84ab-43400846f7e3'),
                'customer_code'=>$data->ref('CUS',$i),'corporate_limit'=>1000000,'channel'=>$data->channel($i),
                'branch_code'=>'001','bank_response_code'=>'TEST','bank_correlation_id'=>TestingDataContext::uuid('onboarding.'.$i),
                'bank_response_payload'=>json_encode(['synthetic'=>true,'status'=>'active']),
            ]));
            $data->row('wallets',['partner_id'=>$partner->id],[
                'id'=>TestingDataContext::uuid('wallet.'.$i),'currency'=>'TZS','status'=>'ACTIVE',
            ]);
            foreach ([1,2,3] as $service) $data->row('mandate_service_partner',['mandate_service_id'=>$service,'partner_id'=>$partner->id],[]);
            foreach (['approver','operator','api'] as $profile) $data->row('partner_users',['user_id'=>$data->id('users',$profile),'partner_id'=>$partner->id],[
                'partner_uuid'=>$partner->uuid,'authorize'=>1,
            ]);
            foreach ([1=>'Business_Licence',2=>'Certificate_of_incorporation',3=>'TIN_Certificate'] as $sequence=>$document) {
                $data->row('partner_documents',['partner_id'=>$partner->id,'key'=>$document],[
                    'partner_uuid'=>$partner->uuid,'name'=>str_replace('_',' ',$document),'attachment'=>'emakato-testing-sample.txt',
                ]);
                $data->row('partner_approvals',['partner_id'=>$partner->id,'sequence'=>$sequence],[
                    'key'=>$document,'label'=>str_replace('_',' ',$document),'status'=>'approved','remarks'=>'Testing approval',
                ]);
            }
            $data->row('partner_staff',['partner_id'=>$partner->id,'email'=>'staff'.$i.'@testing.emakato.invalid'],[
                'partner_uuid'=>$partner->uuid,'name'=>'Test Representative '.$i,'NIDA_number'=>$data->ref('ID',$i),
                'phone'=>$data->phone(300+$i),'title'=>'Director',
            ]);
        }
    }
}
