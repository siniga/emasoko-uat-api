<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Partner::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid(),
            'partner_category_id' => $this->faker->numberBetween(1,3),
            'business_name' => $this->faker->company,
            'year_of_incorporation' => $this->faker->numberBetween(2000, 2023),
            'tin_number' => $this->faker->uuid(),
            'number_of_staff' => $this->faker->numberBetween(10, 20),
            'region_id' => $this->faker->numberBetween(1,32),
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'website' => $this->faker->url,
            'postal_address' => $this->faker->address,
            'physical_address' => $this->faker->streetAddress,
            'complete' => $this->faker->numberBetween(0,1),
            'status' => $this->faker->numberBetween(0,1),
            'created_by' => 1,
        ];
    }
}
