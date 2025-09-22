<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . ' Template',
            'body' => $this->faker->sentence(8),
            'approved' => $this->faker->boolean(80),
        ];
    }
}
