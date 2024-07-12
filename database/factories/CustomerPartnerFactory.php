<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerPartner;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerPartnerFactory extends Factory
{

    protected $model = CustomerPartner::class; 

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'customer_id' => $this->faker->randomElement(Customer::pluck('id')->toArray()),
            'partner_id' => $this->faker->randomElement(Partner::pluck('id')->toArray()),
            'created_by' => $this->faker->randomElement(User::pluck('id')->toArray())
        ];
    }
}
