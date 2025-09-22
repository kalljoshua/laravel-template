<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Campaign;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'lead_id' => Lead::factory(),
            'template_snapshot' => [
                'body' => $this->faker->sentence(8),
                'name' => $this->faker->words(2, true),
            ],
            'status' => $this->faker->randomElement(['pending', 'sent', 'failed']),
            'provider_message_id' => $this->faker->uuid(),
            'last_error' => $this->faker->optional()->sentence(6),
            'scheduled_for' => $this->faker->dateTimeBetween('-1 days', '+2 days'),
        ];
    }
}
