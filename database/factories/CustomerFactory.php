<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'phone' => $this->faker->phoneNumber(),
            'fullname' => $this->faker->name(),
            'dob' => $this->faker->date('Y-m-d', '2000-01-01'),
            'id_type' => 'NIN',
            'id_number' => $this->faker->creditCardNumber(),
            'email' => $this->faker->email(),
            'address' => $this->faker->address(),
            'created_by' => $this->faker->randomElement(User::pluck('id')->toArray())
        ];
    }
}
