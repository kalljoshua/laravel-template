<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WebhookService;

class WebhookController extends Controller
{
    protected $service;

    public function __construct(WebhookService $service)
    {
        $this->service = $service;
    }

    public function receive(Request $request)
    {
        // Handle webhook verification (GET request)
        if ($request->isMethod('get')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            $result = $this->service->verifyWebhook($mode, $token, $challenge);
            
            if ($result) {
                return response($result, 200);
            } else {
                return response('Forbidden', 403);
            }
        }

        // Handle webhook data (POST request)
        $result = $this->service->process($request->all());
        
        if ($result) {
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'error'], 400);
        }
    }
}
