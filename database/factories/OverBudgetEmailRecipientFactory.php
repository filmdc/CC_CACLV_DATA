<?php

namespace Database\Factories;

use App\Models\OverBudgetEmailRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OverBudgetEmailRecipientFactory extends Factory
{
    protected $model = OverBudgetEmailRecipient::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
