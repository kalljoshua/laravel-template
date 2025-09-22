<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            // Generate realistic WhatsApp phone numbers (E.164 format)
            'phone' => $this->faker->unique()->numerify('+2547########'),
            'email' => $this->faker->unique()->safeEmail(),
            'metadata' => [
                'source' => $this->faker->randomElement(['import', 'web', 'manual']),
                'notes' => $this->faker->optional()->sentence(4),
            ],
            'opted_out' => $this->faker->boolean(10),
        ];
    }
}
