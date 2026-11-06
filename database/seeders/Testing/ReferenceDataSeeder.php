<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(TestingDataContext $data): void
    {
        $references = json_decode(file_get_contents(database_path('seeders/data/testing-reference.json')),true,512,JSON_THROW_ON_ERROR);
        foreach ($references as $table=>$rows) {
            foreach ($rows as $row) {
                if (in_array($table,['sms_categories','sms_templates'],true)) $row['uuid'] = TestingDataContext::uuid($table.'.'.$row['id']);
                $data->canonical($table,$row);
            }
        }
        foreach (['Accountant','Architect','Engineer','Medical Practitioner'] as $i=>$name) {
            $data->canonical('tb_occupations',['id'=>$i+1,'title'=>$name]);
        }
        // Never reset the shared numbering counters on an existing testing database.
        $variables = $data->db->table('app_variables')->first();
        if (!$variables) $variables = $data->row('app_variables',['id'=>1],['cur_customer_code'=>1000,'next_customer_code'=>'1001','cur_collection_reference'=>1000,'next_collection_reference'=>1001,'cur_mandate_reference'=>1000]);
        $data->remember('app_variables',1,$variables);
    }
}
