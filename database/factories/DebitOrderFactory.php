<?php

namespace Database\Factories;

use App\Models\CustomerAccount;
use App\Models\DebitOrder;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

class DebitOrderFactory extends Factory
{
    protected $model = DebitOrder::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'partner_id' => $this->faker->randomElement(Partner::pluck('id')->toArray()),
            'customer_account_id' => $this->faker->unique()->randomElement(CustomerAccount::pluck('id')->toArray()),
            'total_amount' => $this->faker->numberBetween(100000,10000000),
            'debit_type' => $this->faker->randomElement(['FIXED', 'DYNAMIC']),
            'installment_amount' => $this->faker->numberBetween(10000,100000),
            'min_installment_amount' => $this->faker->numberBetween(10000,100000),
            'max_installment_amount' => $this->faker->numberBetween(10000,100000),
            'installment_number' => $this->faker->numberBetween(10, 24),
            'periodicity' => $this->faker->randomElement(['MONTHLY', 'WEEKLY']),
            'deduction_end_date' => $this->faker->randomElement(['totalLoanCovered', 'specificDate']),
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime(date('Y-m-d') . '+2 year')),
        ];
    }
}
