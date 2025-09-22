<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageEvent;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function process(array $data)
    {
        try {
            Log::info('Webhook received', ['data' => $data]);

            // Check if this is a WhatsApp webhook
            if (!isset($data['entry'])) {
                Log::warning('Invalid webhook format - missing entry');
                return false;
            }

            foreach ($data['entry'] as $entry) {
                if (!isset($entry['changes'])) {
                    continue;
                }

                foreach ($entry['changes'] as $change) {
                    if ($change['field'] === 'messages') {
                        $this->processMessageWebhook($change['value']);
                    }
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function processMessageWebhook(array $value)
    {
        // Process message status updates
        if (isset($value['statuses'])) {
            foreach ($value['statuses'] as $status) {
                $this->updateMessageStatus($status);
            }
        }

        // Process incoming messages (optional - for replies)
        if (isset($value['messages'])) {
            foreach ($value['messages'] as $message) {
                $this->logIncomingMessage($message);
            }
        }
    }

    private function updateMessageStatus(array $status)
    {
        try {
            $messageId = $status['id'];
            $statusValue = $status['status']; // sent, delivered, read, failed
            $timestamp = $status['timestamp'] ?? time();

            // Find the message by provider_message_id
            $message = Message::where('provider_message_id', $messageId)->first();

            if (!$message) {
                Log::warning("Message not found for provider ID: {$messageId}");
                return;
            }

            // Only update to newer statuses (don't go backwards)
            $statusHierarchy = ['pending' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4];
            $currentLevel = $statusHierarchy[$message->status] ?? 0;
            $newLevel = $statusHierarchy[$statusValue] ?? 0;

            if ($newLevel >= $currentLevel || $statusValue === 'failed') {
                $message->update(['status' => $statusValue]);

                // Create message event
                $message->events()->create([
                    'event_type' => $statusValue,
                    'payload' => [
                        'timestamp' => $timestamp,
                        'webhook_data' => $status
                    ]
                ]);

                Log::info("Message {$message->id} status updated to {$statusValue}");
            }

        } catch (\Exception $e) {
            Log::error('Failed to update message status: ' . $e->getMessage(), [
                'status' => $status
            ]);
        }
    }

    private function logIncomingMessage(array $message)
    {
        // Log incoming message (reply from customer)
        Log::info('Incoming message received', [
            'from' => $message['from'] ?? 'unknown',
            'type' => $message['type'] ?? 'unknown',
            'timestamp' => $message['timestamp'] ?? time(),
            'message_id' => $message['id'] ?? 'unknown'
        ]);

        // You can extend this to store customer replies in database
    }

    /**
     * Verify webhook (for webhook URL verification)
     */
    public function verifyWebhook(string $mode, string $token, string $challenge)
    {
        $verifyToken = config('services.whatsapp.webhook_verify_token');
        
        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $challenge;
        }
        
        return null;
    }
}
