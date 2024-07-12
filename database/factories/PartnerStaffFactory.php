<?php

namespace Database\Factories;

use App\Models\Partner;
use App\Models\PartnerStaff;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerStaffFactory extends Factory
{
    protected $model = PartnerStaff::class;

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
            'name' => $this->faker->name(),
            'NIDA_number' => $this->faker->creditCardNumber(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'title' => 'CEO',
        ];
    }
}
