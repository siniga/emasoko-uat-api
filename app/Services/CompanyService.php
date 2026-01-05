<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\Account;
use App\Models\AppVariable;
use App\Models\CompanyUser;
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
    private $app_variable;
    public function __construct()
    {
        $this->app_variable = AppVariable::first();
    }

    //create company information
    public function createInformation($data, $uuid): Partner
    {
        //logged in user
        $user = User::findOrFail(Auth::user()->id);

        //declare company
        $company_id = null;

        if ($user->hasRole('Partner')) {
            $company_user = CompanyUser::where(['user_id' => Auth::user()->id])->first();

            if ($company_user)
                $company_id = $company_user->company_id;
        }

        //modify phone number
        $phone = $data->company_phone;

        //create or update partner information
        $partner = Partner::firstOrNew(['uuid' => $uuid]);
        if ($partner->created_by == null) {
            $partner->created_by = auth()->id();
        }
        $partner->partner_category_id = $data->category_id;
        $partner->business_name = ucwords($data->business_name);
        $partner->tin_number = $data->tin_number;
        $partner->year_of_incorporation = $data->year_of_incorporation;
        $partner->region_id = $data->region_id;
        $partner->phone = $phone;
        $partner->email = $data->company_email;
        $partner->postal_address = $data->postal_address;
        $partner->physical_address = $data->physical_address;
        $partner->next_step = 'companies/documents/' . $data->partner_uuid;
        $partner->company_id = $company_id;
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
                    // detect account channel
                    $account = Account::where(['id' => $data['account_id'][$i]])->first();

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
                    $partner_account->customer_code = $data['customer_code'][$i];
                    $partner_account->corporate_limit = floatval(str_replace(',', '', $data['corporate_limit'][$i]));
                    $partner_account->branch_code = substr($data['account_number'][$i], 0, 3);
                    $partner_account->currency_id = $data['currency_id'][$i];
                    $partner_account->channel = $account->slug;
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
            $q->where('name', 'Manager');
        })->get();

        if ($users->isNotEmpty()) {
            foreach ($users as $user) {
                //send email in background
                $data = [
                    "to_email" => $user->email,
                    "from_email" => "support@solocodes.io",
                    "name" => $user->name,
                    "subject" => "Corporate Registration",
                    "body" => $message
                ];
                dispatch(new SendEmailJob($data));
            }
        }
    }

    //process company assessment
    public function processAssessment(Partner $partner, $data)
    {
        if ($data['action'] == "REJECT") {
            //update company registration status
            $partner->complete = 0;
            $partner->approved = 99;
            $partner->remarks = $data['remarks'];
            $partner->updated_by = auth()->id();
            $partner->save();

            //construct message
            $message = "<p>Company information has been rejected by eMakato Team.</p>";
            $message .= "<p>Reason for rejection:" . $data['remarks'] . "</p>";
            $message .= "<p>Please, contact with eMakato Team to complete your information</p>";

            //send email
            $data = [
                "to_email" => $partner->email,
                "from_email" => "support@solocodes.io",
                "name" => $partner->business_name,
                "subject" => "eMakato Corporate Registration",
                "body" => $message
            ];
            dispatch(new SendEmailJob($data));

            //return statement
            return 500;
        } else if ($data['action'] == "APPROVE") {
            //update company registration status
            $partner->complete = 1;
            $partner->approved = 1; //approved
            $partner->remarks = "Service provider registration approved by eMakato Team";
            $partner->status = 200; //OK
            $partner->updated_by = auth()->id();
            $partner->save();

            return 200;
        }
    }

    //create partner credentials
    public function createCredential(Partner $partner): void
    {
        //token and password
        $token = Str::random(64);
        $password = "12345678";

        //partners staff
        $staff = PartnerStaff::where(['partner_uuid' => $partner->uuid])->get();

        if ($staff) {
            foreach ($staff as $val) {
                //create user to store data
                $user = User::firstOrNew(['email' => $val->email, 'TIN_number' => $val->tin_number]);
                $user->uuid = Str::uuid();
                $user->name = $val->name;
                $user->phone = $val->phone;
                $user->verify_token = $token;
                $user->password = Hash::make($password);
                $user->otp_password = base64_encode($password);
                $user->active = 1;
                $user->save();
            }
        }



        //create user to store data
        $user = User::firstOrNew(['email' => $partner->email, 'TIN_number' => $partner->tin_number]);
        $user->uuid = Str::uuid();
        $user->name = $partner->business_name;
        $user->phone = $partner->phone;
        $user->verify_token = $token;
        $user->password = Hash::make($password);
        $user->otp_password = base64_encode($password);
        $user->active = 1;
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

    //get customer code
    public function getCustomerCode()
    {
        static $number = $this->app_variable->cur_customer_code;

        $number++;
        $next_customer_code = str_pad($number, 9, '0', STR_PAD_LEFT);

        //update app variable
        $this->app_variable->cur_customer_code = $number;
        $this->app_variable->next_customer_code = $next_customer_code;
        $this->app_variable->save();

        return $next_customer_code;
    }
}
