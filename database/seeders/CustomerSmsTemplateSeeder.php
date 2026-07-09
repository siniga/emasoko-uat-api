<?php

namespace Database\Seeders;

use App\Models\SmsCategory;
use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CustomerSmsTemplateSeeder extends Seeder
{
    public function run()
    {
        $category = SmsCategory::firstOrCreate(
            ['name' => 'Customer Notifications'],
            ['uuid' => (string) Str::uuid()]
        );

        $templates = [
            [
                'code' => 'mandate_created',
                'channel' => 'NMB',
                'name' => 'Mandate Creation Successful Confirmation - NMB',
                'message' => 'Habari, huduma ya makato kwenda {service_provider} imeanzishwa kikamilifu kwenye akaunti yako inayoishia {account_last_digits}. Asante kwa kutumia eMakato.',
            ],
            [
                'code' => 'mandate_created',
                'channel' => 'CRDB',
                'name' => 'Mandate Creation Successful Confirmation - CRDB',
                'message' => 'Habari, huduma ya makato kupitia SoloCodes kwenda {service_provider} imeanzishwa kikamilifu kwenye akaunti yako inayoishia {account_last_digits}. Asante',
            ],
            [
                'code' => 'collection_batch_uploaded',
                'channel' => 'NMB',
                'name' => 'Batch Uploading - NMB',
                'message' => 'Habari, ombi la makato la TSh {amount} la tarehe {payment_date} kutoka kwenye akaunti yako inayoishia {account_last_digits} kwenda {service_provider} limewasilishwa Benki.',
            ],
            [
                'code' => 'deduction_success',
                'channel' => 'ALL',
                'name' => 'Successful Deduction',
                'message' => 'Makato ya tarehe {payment_date}, kiasi cha shilingi {amount} yamekamilishwa kwa {service_provider}. Kiasi kilichobaki ni Tsh {outstanding_amount}.',
            ],
            [
                'code' => 'deduction_failed',
                'channel' => 'ALL',
                'name' => 'Unsuccessful Deduction',
                'message' => 'Makato ya tarehe {payment_date} ya TSh {amount} kwenda {service_provider} hayakufanikiwa. Tafadhali weka fedha za kutosha kwenye akaunti yako inayoishia {account_last_digits}. Asante',
            ],
            [
                'code' => 'partial_deduction',
                'channel' => 'CRDB',
                'name' => 'CRDB Partial Deduction',
                'message' => 'Kiasi cha TSh {amount} kimekatwa kwenda {service_provider}. Tafadhali weka TSh {outstanding_amount} kwenye akaunti yako ili kukamilisha makato ya mwezi. Asante.',
            ],
            [
                'code' => 'mandate_closed',
                'channel' => 'ALL',
                'name' => 'Mandate Closed',
                'message' => 'Habari, makato yenye namba ya kumbukumbu {mandate_reference} kwenda kwa {service_provider} yamefungwa kikamilifu. Asante kwa kutumia eMakato.',
            ],
            [
                'code' => 'mandate_cancelled',
                'channel' => 'NMB',
                'name' => 'Closed/Canceled Mandate - NMB',
                'message' => 'Habari, makato yenye namba ya kumbukumbu {mandate_reference} kwenda kwa {service_provider} yamefungwa kikamilifu. Asante kwa kutumia eMakato.',
            ],
            [
                'code' => 'mandate_cancelled',
                'channel' => 'CRDB',
                'name' => 'Closed/Canceled Mandate - CRDB',
                'message' => 'Habari, makato yenye namba ya kumbukumbu {mandate_reference} kwenda kwa {service_provider} yamefungwa kikamilifu. Asante kwa kutumia eMakato.',
            ],
        ];

        foreach ($templates as $template) {
            $smsTemplate = SmsTemplate::firstOrNew([
                'code' => $template['code'],
                'channel' => $template['channel'],
            ]);

            if (!$smsTemplate->exists) {
                $smsTemplate->uuid = (string) Str::uuid();
            }

            $smsTemplate->name = $template['name'];
            $smsTemplate->category_id = $category->id;
            $smsTemplate->message = $template['message'];
            $smsTemplate->is_active = true;
            $smsTemplate->save();
        }
    }
}
