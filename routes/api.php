<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('campaigns', CampaignController::class);
Route::post('campaigns/{id}/start', [CampaignController::class, 'start']);
Route::post('campaigns/{id}/stop', [CampaignController::class, 'stop']);
Route::get('campaigns/{id}/stats', [CampaignController::class, 'stats']);

Route::apiResource('templates', TemplateController::class);
Route::apiResource('leads', LeadController::class);
Route::post('leads/upload', [LeadController::class, 'upload']);

// Webhook endpoints (both GET for verification and POST for data)
Route::match(['get', 'post'], 'webhook', [WebhookController::class, 'receive']);
