<?php

namespace App\Http\Controllers\Api;

use App\Models\Disease;
use Illuminate\Http\Request;
use App\Http\Requests\DiseaseRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\DiseaseResource;

class DiseaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $diseases = Disease::with('priority')->paginate();

        return DiseaseResource::collection($diseases);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DiseaseRequest $request): JsonResponse
    {
        $disease = Disease::create($request->validated());

        return response()->json(new DiseaseResource($disease));
    }

    /**
     * Display the specified resource.
     */
    public function show(Disease $disease): JsonResponse
    {
        $disease->loadMissing('priority');

        return response()->json(new DiseaseResource($disease));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DiseaseRequest $request, Disease $disease): JsonResponse
    {
        $disease->update($request->validated());

        return response()->json(new DiseaseResource($disease));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Disease $disease): Response
    {
        $disease->delete();

        return response()->noContent();
    }
}
