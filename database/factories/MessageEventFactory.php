<?php

namespace Database\Factories;

use App\Models\MessageEvent;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageEventFactory extends Factory
{
    protected $model = MessageEvent::class;

    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'event_type' => $this->faker->randomElement(['delivered', 'read', 'failed']),
            'payload' => ['info' => $this->faker->sentence(4)],
        ];
    }
}
