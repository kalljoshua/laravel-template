<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Message;
use App\Models\MessageEvent;
use App\Jobs\SendCampaignMessagesJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function list()
    {
        return Campaign::with(['template', 'messages'])->get();
    }

    public function create(array $data)
    {
        return Campaign::create($data);
    }

    public function get($id)
    {
        return Campaign::with(['template', 'messages.lead', 'messages.events'])->findOrFail($id);
    }

    public function update($id, array $data)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->update($data);
        return $campaign;
    }

    public function delete($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->delete();
    }

    /**
     * Start sending a campaign to all leads
     */
    public function startCampaign($campaignId)
    {
        DB::transaction(function () use ($campaignId) {
            $campaign = Campaign::with('template')->findOrFail($campaignId);
            
            if ($campaign->status !== 'pending') {
                throw new \Exception('Campaign must be in pending status to start');
            }

            // Get all leads
            $leads = Lead::all();
            
            if ($leads->isEmpty()) {
                throw new \Exception('No leads available to send campaign to');
            }

            // Create message records for each lead
            $messages = [];
            foreach ($leads as $lead) {
                $messages[] = [
                    'campaign_id' => $campaign->id,
                    'lead_id' => $lead->id,
                    'template_snapshot' => json_encode($campaign->template->toArray()),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Message::insert($messages);

            // Update campaign status
            $campaign->update(['status' => 'running']);

            // Dispatch job to send messages with rate limiting
            SendCampaignMessagesJob::dispatch($campaign->id);
            
            Log::info("Campaign {$campaign->id} started with " . count($messages) . " messages");
        });

        return $this->get($campaignId);
    }

    /**
     * Stop a running campaign
     */
    public function stopCampaign($campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        
        if ($campaign->status !== 'running') {
            throw new \Exception('Campaign must be running to stop');
        }

        // Update campaign status
        $campaign->update(['status' => 'stopped']);

        // Update pending messages to cancelled
        Message::where('campaign_id', $campaignId)
               ->where('status', 'pending')
               ->update(['status' => 'cancelled']);

        Log::info("Campaign {$campaignId} stopped");

        return $this->get($campaignId);
    }

    /**
     * Get campaign statistics
     */
    public function getCampaignStats($campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        
        $stats = Message::where('campaign_id', $campaignId)
                        ->selectRaw('
                            status,
                            COUNT(*) as count
                        ')
                        ->groupBy('status')
                        ->pluck('count', 'status')
                        ->toArray();

        $total = array_sum($stats);
        
        return [
            'campaign_id' => $campaignId,
            'campaign_name' => $campaign->name,
            'total_messages' => $total,
            'pending' => $stats['pending'] ?? 0,
            'sent' => $stats['sent'] ?? 0,
            'delivered' => $stats['delivered'] ?? 0,
            'read' => $stats['read'] ?? 0,
            'failed' => $stats['failed'] ?? 0,
            'cancelled' => $stats['cancelled'] ?? 0,
        ];
    }
}
