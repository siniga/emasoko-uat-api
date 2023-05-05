<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
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
            'dob' => $this->faker->date(),
            'id_type' => $this->faker->randomDigit,
            'id_number' => $this->faker->numberBetween(2, 30),
        ];
    }
}
