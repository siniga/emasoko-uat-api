<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Partner;
use App\Models\PartnerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerAccountFactory extends Factory
{
    protected $model = PartnerAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'partner_id' => $this->faker->unique()->randomElement(Partner::pluck('id')->toArray()),
            'partner_uuid' => $this->faker->unique()->randomElement(Partner::pluck('uuid')->toArray()),
            'account_type_id' => $this->faker->randomElement(AccountType::pluck('id')->toArray()),
            'account_id' => $this->faker->randomElement(Account::pluck('id')->toArray()),
            'account_name' => $this->faker->company(),
            'account_number' => $this->faker->creditCardNumber()
        ];
    }
}
