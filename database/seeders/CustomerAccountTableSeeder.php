<?php

namespace Database\Seeders;

use App\Models\CustomerAccount;
use Illuminate\Database\Seeder;

class CustomerAccountTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\CustomerAccount::factory(10)->create();
    }
}
