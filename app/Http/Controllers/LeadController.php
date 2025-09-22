<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeadService;

class LeadController extends Controller
{
    protected $service;

    public function __construct(LeadService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->list());
    }

    public function store(Request $request)
    {
        $lead = $this->service->create($request->all());
        return response()->json($lead, 201);
    }

    public function show($id)
    {
        return response()->json($this->service->get($id));
    }

    public function update(Request $request, $id)
    {
        $lead = $this->service->update($id, $request->all());
        return response()->json($lead);
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $count = $this->service->uploadCsv($request->file('file'));
        return response()->json(['message' => "Uploaded {$count} leads successfully", 'count' => $count]);
    }
}
