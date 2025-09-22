<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . ' Campaign',
            'template_id' => Template::factory(),
            'rate_limit_per_minute' => $this->faker->numberBetween(10, 100),
            'status' => $this->faker->randomElement(['pending', 'running', 'completed']),
        ];
    }
}
