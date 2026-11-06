<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;

class IdentityDataSeeder extends Seeder
{
    public function run(TestingDataContext $data): void
    {
        $profiles = ['admin'=>1,'approver'=>2,'operator'=>4,'partner'=>6,'manager'=>7,'subscriber.admin'=>8,'subscriber.user'=>9,'api'=>4];
        $keys = array_keys($profiles);
        for ($i=1;$i<=$data->count;$i++) {
            $profile = $keys[($i-1)%count($keys)];
            $slug = $i <= count($keys) ? $profile : 'user.'.str_pad((string)$i,3,'0',STR_PAD_LEFT);
            $user = $data->row('users',['email'=>$slug.'@testing.emakato.invalid'],[
                'uuid'=>TestingDataContext::uuid('user.'.$i),'name'=>'Test '.ucwords(str_replace('.',' ',$slug)),
                'phone'=>$data->phone($i),'NIDA_number'=>'TEST-ID-'.$i,'email_verified_at'=>$data->date,
                'password'=>$data->passwordHash(),
                // The current AuthOtpController also checks this legacy field.
                'otp_password'=>base64_encode(config('testing-data.password')),
                'active'=>1,'username'=>'test.'.$slug,'is_api_user'=>$profile==='api' ? 1 : 0,
            ]);
            $data->remember('users',$i,$user);
            if ($i<=count($keys)) $data->remember('users',$profile,$user);
            $data->row('role_user',['user_id'=>$user->id,'role_id'=>$profiles[$profile]],[]);
            // Only expired/used history is inserted. Real sign-ins generate their own OTP.
            $data->row('user_otps',['user_id'=>$user->id,'otp'=>90000+$i],[
                'phone'=>$user->phone,'status'=>1,'expire_at'=>$data->date->subDay(),
            ]);
        }
        $class = $data->row('perm_classes',['class'=>\App\Http\Controllers\DashboardController::class],['name'=>'Dashboard','ordering'=>1]);
        $method = $data->row('perm_methods',['class_id'=>$class->id,'method'=>'index'],['name'=>'View dashboard']);
        foreach ($profiles as $role) $data->row('perm_roles',['role_id'=>$role],['classes'=>(string)$class->id,'methods'=>(string)$method->id]);
        $data->row('perm_users',['user_id'=>$data->id('users','admin')],['classes'=>(string)$class->id,'methods'=>(string)$method->id]);
        for ($i=1;$i<=3;$i++) {
            $data->remember('companies',$i,$data->row('companies',['company_email'=>'company'.$i.'@testing.emakato.invalid'],[
                'company_name'=>'Test Company '.$i,'company_phone'=>$data->phone(100+$i),'tin_number'=>'TEST-TIN-'.$i,
                'created_by'=>$data->id('users','admin'),'updated_by'=>$data->id('users','admin'),
            ]));
            foreach ([1,2,3,4,5] as $category) $data->row('company_partner_categories',['company_id'=>$data->id('companies',$i),'partner_category_id'=>$category],[]);
        }
        $data->row('company_users',['company_id'=>$data->id('companies',1),'user_id'=>$data->id('users','partner')],['authorize'=>1]);
        foreach (['personal'=>true,'password'=>false] as $kind=>$personal) {
            $client = $data->row('oauth_clients',['name'=>'Emakato Testing '.$kind],[
                'user_id'=>null,'secret'=>bin2hex(random_bytes(32)),'provider'=>$personal?null:'users',
                'redirect'=>'https://testing.emakato.invalid/oauth/callback','personal_access_client'=>$personal?1:0,
                'password_client'=>$personal?0:1,'revoked'=>0,
            ]);
            $data->remember('oauth_clients',$kind,$client);
            if ($personal) $data->row('oauth_personal_access_clients',['client_id'=>$client->id],[]);
        }
    }
}
