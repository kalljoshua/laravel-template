<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $apiUrl;
    private $accessToken;
    private $phoneNumberId;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }

    /**
     * Send a WhatsApp message using a template
     */
    public function sendTemplateMessage(string $recipientPhone, array $template, array $variables = [])
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatPhoneNumber($recipientPhone),
                'type' => 'template',
                'template' => [
                    'name' => $template['name'],
                    'language' => [
                        'code' => $template['language'] ?? 'en_US'
                    ]
                ]
            ];

            // Add template parameters if variables provided
            if (!empty($variables)) {
                $payload['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => array_map(function($var) {
                            return ['type' => 'text', 'text' => $var];
                        }, $variables)
                    ]
                ];
            }

            $response = Http::withToken($this->accessToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'message_id' => $result['messages'][0]['id'] ?? null,
                    'response' => $result
                ];
            } else {
                Log::error('WhatsApp API Error', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);

                return [
                    'success' => false,
                    'error' => $response->json()['error']['message'] ?? 'API request failed',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test connection to WhatsApp API
     */
    public function testConnection()
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get("{$this->apiUrl}/{$this->phoneNumberId}");

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number for WhatsApp API (remove + and non-numeric characters)
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // Remove leading + if present
        $cleaned = ltrim($cleaned, '+');
        
        return $cleaned;
    }

    /**
     * Verify webhook signature (for security)
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $appSecret = config('services.whatsapp.app_secret');
        
        if (!$appSecret) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
        
        return hash_equals($expectedSignature, $signature);
    }
}