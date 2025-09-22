<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Templates
        $templates = \App\Models\Template::factory()->count(5)->create();

        // Campaigns (each linked to a template)
        $campaigns = collect();
        foreach ($templates as $template) {
            $campaigns = $campaigns->merge(
                \App\Models\Campaign::factory()->count(2)->create([
                    'template_id' => $template->id,
                ])
            );
        }

        // Leads
        // Seed two specific WhatsApp numbers, rest as placeholders
        $leads = collect([
            \App\Models\Lead::create([
                'name' => 'John Doe',
                'phone' => '+256752306399',
                'email' => 'john.doe@example.com',
                'metadata' => ['source' => 'manual', 'notes' => 'VIP lead'],
            ]),
            \App\Models\Lead::create([
                'name' => 'Jane Smith',
                'phone' => '+256787146211',
                'email' => 'jane.smith@example.com',
                'metadata' => ['source' => 'manual', 'notes' => 'Priority'],
            ]),
        ]);
        $leads = $leads->merge(\App\Models\Lead::factory()->count(18)->create());

        // Messages (each linked to a campaign and a lead)
        $messages = collect();
        foreach ($campaigns as $campaign) {
            foreach ($leads->random(5) as $lead) {
                $messages->push(
                    \App\Models\Message::factory()->create([
                        'campaign_id' => $campaign->id,
                        'lead_id' => $lead->id,
                        'template_snapshot' => [
                            'body' => $campaign->template->body,
                            'name' => $campaign->template->name,
                        ],
                    ])
                );
            }
        }

        // MessageEvents (each linked to a message)
        foreach ($messages as $message) {
            \App\Models\MessageEvent::factory()->count(2)->create([
                'message_id' => $message->id,
            ]);
        }
    }
}
