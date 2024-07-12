<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAccountFactory extends Factory
{
    protected $model = CustomerAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid(),
            'customer_id' => $this->faker->unique()->randomElement(Customer::pluck('id')->toArray()),
            'account_type_id' => $this->faker->randomElement(AccountType::pluck('id')->toArray()),
            'account_id' => $this->faker->randomElement(Account::pluck('id')->toArray()),
            'account_name' => $this->faker->company(),
            'account_number' => $this->faker->creditCardNumber(),
            'phone' => $this->faker->phoneNumber(),
            'created_by' => $this->faker->randomElement(User::pluck('id')->toArray())
        ];
    }
}
