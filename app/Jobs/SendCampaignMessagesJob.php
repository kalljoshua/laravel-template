<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Message;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendCampaignMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaignId;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function handle(WhatsAppService $whatsappService)
    {
        $campaign = Campaign::with('template')->findOrFail($this->campaignId);
        
        if ($campaign->status !== 'running') {
            Log::info("Campaign {$this->campaignId} is not running, stopping job");
            return;
        }

        // Get pending messages for this campaign
        $pendingMessages = Message::with('lead')
            ->where('campaign_id', $this->campaignId)
            ->where('status', 'pending')
            ->limit($campaign->rate_limit_per_minute ?: 10) // Default to 10 if no limit set
            ->get();

        if ($pendingMessages->isEmpty()) {
            // All messages sent, mark campaign as completed
            $campaign->update(['status' => 'completed']);
            Log::info("Campaign {$this->campaignId} completed - all messages sent");
            return;
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($pendingMessages as $message) {
            try {
                // Send the WhatsApp message
                $result = $whatsappService->sendTemplateMessage(
                    $message->lead->phone,
                    [
                        'name' => $message->template_snapshot['name'],
                        'language' => $message->template_snapshot['language'] ?? 'en_US'
                    ],
                    $this->prepareTemplateVariables($message->lead, $message->template_snapshot)
                );

                if ($result['success']) {
                    $message->update([
                        'status' => 'sent',
                        'provider_message_id' => $result['message_id'],
                        'last_error' => null
                    ]);
                    $sentCount++;

                    // Log message event
                    $message->events()->create([
                        'event_type' => 'sent',
                        'payload' => $result['response']
                    ]);

                } else {
                    $message->update([
                        'status' => 'failed',
                        'last_error' => $result['error']
                    ]);
                    $failedCount++;

                    // Log error event
                    $message->events()->create([
                        'event_type' => 'failed',
                        'payload' => ['error' => $result['error']]
                    ]);
                }

            } catch (\Exception $e) {
                $message->update([
                    'status' => 'failed',
                    'last_error' => $e->getMessage()
                ]);
                $failedCount++;

                Log::error("Failed to send message {$message->id}: " . $e->getMessage());
            }
        }

        Log::info("Campaign {$this->campaignId} batch processed: {$sentCount} sent, {$failedCount} failed");

        // Check if there are more messages to send
        $remainingCount = Message::where('campaign_id', $this->campaignId)
            ->where('status', 'pending')
            ->count();

        if ($remainingCount > 0) {
            // Schedule next batch after 1 minute (to respect rate limits)
            static::dispatch($this->campaignId)->delay(Carbon::now()->addMinute());
        } else {
            // All messages processed, mark campaign as completed
            $campaign->update(['status' => 'completed']);
            Log::info("Campaign {$this->campaignId} completed");
        }
    }

    /**
     * Prepare template variables from lead data
     */
    private function prepareTemplateVariables($lead, $templateSnapshot)
    {
        $variables = [];
        
        // Map lead fields to template variables
        if (isset($templateSnapshot['body']) && strpos($templateSnapshot['body'], '{{name}}') !== false) {
            $variables[] = $lead->name ?? 'Customer';
        }
        
        return $variables;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        Log::error("SendCampaignMessagesJob failed for campaign {$this->campaignId}: " . $exception->getMessage());
        
        // Mark campaign as failed
        Campaign::where('id', $this->campaignId)->update(['status' => 'failed']);
        
        // Mark pending messages as failed
        Message::where('campaign_id', $this->campaignId)
            ->where('status', 'pending')
            ->update(['status' => 'failed', 'last_error' => 'Job failed: ' . $exception->getMessage()]);
    }
}