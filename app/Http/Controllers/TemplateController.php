<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TemplateService;

class TemplateController extends Controller
{
    protected $service;

    public function __construct(TemplateService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->list());
    }

    public function store(Request $request)
    {
        $template = $this->service->create($request->all());
        return response()->json($template, 201);
    }

    public function show($id)
    {
        return response()->json($this->service->get($id));
    }

    public function update(Request $request, $id)
    {
        $template = $this->service->update($id, $request->all());
        return response()->json($template);
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
