<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Services\CampaignService;

class CampaignController extends Controller
{
    protected $service;

    public function __construct(CampaignService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->list());
    }

    public function store(Request $request)
    {
        $campaign = $this->service->create($request->all());
        return response()->json($campaign, 201);
    }

    public function show($id)
    {
        return response()->json($this->service->get($id));
    }

    public function update(Request $request, $id)
    {
        $campaign = $this->service->update($id, $request->all());
        return response()->json($campaign);
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function start($id)
    {
        try {
            $campaign = $this->service->startCampaign($id);
            return response()->json([
                'message' => 'Campaign started successfully',
                'campaign' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function stop($id)
    {
        try {
            $campaign = $this->service->stopCampaign($id);
            return response()->json([
                'message' => 'Campaign stopped successfully',
                'campaign' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function stats($id)
    {
        try {
            $stats = $this->service->getCampaignStats($id);
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
