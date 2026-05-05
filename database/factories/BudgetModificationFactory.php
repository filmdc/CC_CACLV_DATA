<?php

namespace Database\Factories;

use App\Models\BudgetModification;
use App\Models\ProjectBudget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetModificationFactory extends Factory
{
    protected $model = BudgetModification::class;

    public function definition(): array
    {
        return [
            'project_budget_id' => ProjectBudget::factory(),
            'modified_by_user_id' => User::factory(),
            'old_amount' => 1000,
            'new_amount' => 1500,
            'comment' => 'Adjusting allocation to cover newly identified work scope.',
        ];
    }
}
