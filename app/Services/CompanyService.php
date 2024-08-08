<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\AppVariable;
use App\Models\DocumentCategory;
use App\Models\Partner;
use App\Models\PartnerAccount;
use App\Models\PartnerApproval;
use App\Models\PartnerDocument;
use App\Models\PartnerStaff;
use App\Models\PartnerUser;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Auth;

class CompanyService
{
    public function __construct()
    {
    }

    //create company information
    public function createInformation($data, $uuid): Partner
    {
        //modify phone number
        $phone = $data->country_code . cast_phone($data->company_phone);

        //create or update partner information
        $partner = Partner::firstOrNew(['uuid' => $uuid]);
        if ($partner->created_by == null) {
            $partner->created_by = auth()->id();
        }
        $partner->partner_category_id = $data->category_id;
        $partner->business_name = ucwords($data->business_name);
        $partner->tin_number = $data->tin_number;
        $partner->year_of_incorporation = $data->year_of_incorporation;
        $partner->number_of_staff = $data->number_of_staff;
        $partner->region_id = $data->region_id;
        $partner->phone = $phone;
        $partner->email = $data->company_email;
        $partner->postal_address = $data->postal_address;
        $partner->physical_address = $data->physical_address;
        $partner->website = $data->company_website;
        $partner->next_step = 'companies/documents/' . $data->partner_uuid;
        $partner->save();

        return $partner;
    }

    //update partner step
    public function updateNextStep($id, $next_stage): Partner
    {
        //partner
        $partner = Partner::findOrFail($id);
        $partner->next_step = $next_stage;
        $partner->save();

        return $partner;
    }

    //create collection account
    public function createCollectionAccount($data, Partner $partner): void
    {
        if ($data['account_id']) {
            for ($i = 0; $i < sizeof($data['account_id']); $i++) {
                if ($data['account_id'][$i] != '' && $data['account_name'][$i] != '' && $data['account_name'][$i] != '') {
                    //create new partner account
                    $partner_account = PartnerAccount::firstOrNew([
                        'partner_id' => $partner['id'],
                        'partner_uuid' => $partner['uuid'],
                        'account_type_id' => $data['account_type_id'][$i],
                        'account_id' => $data['account_id'][$i],
                        'account_name' => $data['account_name'][$i],
                        'account_number' => $data['account_number'][$i],
                    ]);
                    $partner_account->send_verification = 1;
                    $partner->customer_code = $this->getCustomerCode();
                    $partner->corporate_limit = floatval(str_replace(',', '', $data['corporate_limit'][$i]));
                    $partner->branch_code = $data['branch_code'][$i];
                    $partner->currency_id = $data['currency_id'][$i];
                    $partner_account->save();
                }
            }
        }
    }

    //create company staff
    public function createStaff($data, Partner $partner): void
    {
        if ($data['staff_name']) {
            for ($i = 0; $i < sizeof($data['staff_name']); $i++) {
                if ($data['staff_name'][$i] != '' && $data['nida_number'][$i] != '' && $data['staff_phone'][$i] != '' && $data['staff_title'][$i] != '') {
                    $partner_staff = PartnerStaff::firstOrNew([
                        'partner_id' => $partner->id,
                        'partner_uuid' => $partner->uuid,
                        'NIDA_number' => $data['nida_number'][$i],
                        'name' => $data['staff_name'][$i],
                        'phone' => $data['staff_phone'][$i],
                        'email' => $data['staff_email'][$i],
                        'title' => $data['staff_title'][$i],
                    ]);
                    $partner_staff->save();
                }
            }
        }
    }

    //create partner credentials
    public function createCredential($data, Partner $partner): void
    {
        //token and password
        $token = Str::random(64);
        $password = $data->password;

        //create user to store data
        $user = User::firstOrNew(['email' => $data->email, 'TIN_number' => $data->tin_number]);
        $user->name = $data->name;
        $user->phone = $data->phone;
        $user->verify_token = $token;
        $user->password = Hash::make($password);
        $user->save();

        //insert or create role
        $role_user = RoleUser::firstOrNew([
            'user_id' => $user->id,
            'role_id' => 2
        ]);
        $role_user->save();

        //partner user
        $partner_user = PartnerUser::firstOrNew([
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'partner_uuid' => $partner->uuid
        ]);
        $partner_user->authorize = 1;
        $partner_user->save();
    }

    //create assessment stages
    public function createAssessmentStages(Partner $partner): void
    {
        //assessment criteria
        $arr_assessment_criteria = [
            'Company_Information' => 'KYC Information',
            'Company_Documents' => 'Documents',
            'Company_Collection_Accounts' => 'Collection Accounts',
            'Company_Authorized_Representative' => 'Authorized Representative',
        ];

        $serial = 1;
        foreach ($arr_assessment_criteria as $key => $label) {
            $partner_approval = PartnerApproval::firstOrNew([
                'partner_id' => $partner->id,
                'key' => $key,
                'label' => $label,
                'sequence' => $serial
            ]);
            $partner_approval->save();

            //increment serial 
            $serial++;
        }
    }

    //send alert to eMakato Manager for assessment 
    public function sendAssessmentAlert(Partner $partner): void
    {
        //construct message
        $message = "<p><span class='font-weight-600'>{$partner->business_name}</span> completed registration process. Now your are required to perform assessment and approval process.</p>";
        $message .= "<p>Below are the information of service provider:</p>";
        $message .= "<p>Business Name: <span class='font-weight-500'>{$partner->business_name}</span><br />";
        $message .= "Category: <span class='font-weight-500'>{$partner->partnerCategory->name}</span><br />";
        $message .= "TIN Number: <span class='font-weight-500'>{$partner->tin_number}</span><br />";
        $message .= "Year of Incorporation: <span class='font-weight-500'>{$partner->year_of_incorporation}</span><br />";
        $message .= "Email: <span class='font-weight-500'>{$partner->email}</span><br />";
        $message .= "Phone: <span class='font-weight-500'>{$partner->phone}</span></p>";

        //query for users
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'manager');
        })->get();

        if ($users->isNotEmpty()) {
            foreach ($users as $user) {
                //send email in background
                $data = [
                    "email" => $user->email,
                    "name" => $user->name,
                    "subject" => "Corporate Registration",
                    "body" => $message
                ];
                dispatch(new SendEmailJob($data));
            }
        }
    }

    //process company assessment
    function processAssessment(Partner $partner, $action)
    {
        if ($action == "REJECTED") {
            //update company registration status
            $partner->status = 403; //forbiden
            $partner->updated_by = auth()->id();
            $partner->save();

            //deactivate partner users
            if ($partner->partnerUsers->isNotEmpty()) {
                foreach ($partner->partnerUsers as $val) {
                    //update user
                    $user = User::findOrFail($val->user_id);
                    $user->active = 0;
                    $user->save();
                }
            }

            //construct message
            $message = "<p>Company information has been rejected by eMakato.</p>";
            $message .= "<p>Please, contact with eMakato Team to complete your information</p>";

            //send email
            $data = [
                "email" => $partner->email,
                "name" => $partner->business_name,
                "subject" => "eMakato Corporate Registration",
                "body" => $message
            ];
            dispatch(new SendEmailJob($data));

            //return statement
            return 403;
        } else if ($action == "APPROVE") {
            //update company registration status
            $partner->status = 200; //OK
            $partner->updated_by = auth()->id();
            $partner->save();

            if ($partner->partnerUsers->isNotEmpty()) {
                foreach ($partner->partnerUsers as $val) {
                    //update user
                    $user = User::findOrFail($val->user_id);
                    $user->active = 1;
                    $user->save();
                }
            }

            return 200;
        }
    }

    //get Customer Code
    function getCustomerCode()
    {
        //app variable
        $app_variable = AppVariable::first();

        static $number = $app_variable->cur_customer_code;

        $number++;
        $next_customer_code = str_pad($number, 9, '0', STR_PAD_LEFT);

        //update app variable
        $app_variable->cur_customer_code = $number;
        $app_variable->next_customer_code = $next_customer_code;
        $app_variable->save();

        return $next_customer_code;
    }
}
