<?php

namespace App\Classes;

use App\Models\Partner;
use App\Models\PartnerApproval;
use App\Models\PartnerUser;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class CompanyAssessment
{
    public function __construct()
    {
    }

    function process($company_id)
    {
        //check pending, rejected or approved
        $pending_approvals = PartnerApproval::where(['partner_id' => $company_id])
            ->where(function ($query) {
                $query->where('status', '=', 'pending')
                    ->orWhere('status', '=', 'rejected');
            });

        if ($pending_approvals->count() > 0) {
            //update company registration status
            $company = Partner::findOrFail($company_id);
            $company->status = 2;
            $company->save();

            //partner users
            $p_users = PartnerUser::where(['partner_id' => $company->id])->get();

            if ($p_users->isNotEmpty()) {
                foreach ($p_users as $val) {
                    //update user
                    $user = User::findOrFail($val->user_id);
                    $user->active = 0;
                    $user->save();
                }
            }
            return false;
        } else {
            //update company registration status
            $company = Partner::findOrFail($company_id);
            $company->status = 3;
            $company->save();

            //partner users
            $p_users = PartnerUser::where(['partner_id' => $company->id])->get();

            if ($p_users->isNotEmpty()) {
                foreach ($p_users as $val) {
                    //update user
                    $user = User::findOrFail($val->user_id);
                    $user->active = 1;
                    $user->save();
                }
            }

            //todo: register ALL authorized users


            //send email to company email
            //construct message
            $message = "Company registration has been approved by eMakato.";
            $message .= "<br/>You can start using your eMakato account now. Login to your account by <a href=" . url('login') . ">click here.</a>";

            //send email
            Mail::send(
                ['html' => 'emails.template'],
                ['name' => $user->name, 'body' => $message],
                function ($message) use ($user) {
                    $message->to($user->email);
                    $message->subject('Company Assessment Approval');
                    $message->from('renfridfrancis@gmail.com', 'eMakato');
                }
            );
            return true;
        }
    }
}
