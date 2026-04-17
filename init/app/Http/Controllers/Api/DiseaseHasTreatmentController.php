<?php

namespace App\Http\Controllers\Api;

use App\Models\DiseaseHasTreatment;
use Illuminate\Http\Request;
use App\Http\Requests\DiseaseHasTreatmentRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\DiseaseHasTreatmentResource;

class DiseaseHasTreatmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $diseaseHasTreatments = DiseaseHasTreatment::with(['drug', 'disease'])->paginate();

        return DiseaseHasTreatmentResource::collection($diseaseHasTreatments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DiseaseHasTreatmentRequest $request): JsonResponse
    {
        $diseaseHasTreatment = DiseaseHasTreatment::create($request->validated());

        return response()->json(new DiseaseHasTreatmentResource($diseaseHasTreatment));
    }

    /**
     * Display the specified resource.
     */
    public function show(DiseaseHasTreatment $diseaseHasTreatment): JsonResponse
    {
        $diseaseHasTreatment->loadMissing(['drug', 'disease']);

        return response()->json(new DiseaseHasTreatmentResource($diseaseHasTreatment));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DiseaseHasTreatmentRequest $request, DiseaseHasTreatment $diseaseHasTreatment): JsonResponse
    {
        $diseaseHasTreatment->update($request->validated());

        return response()->json(new DiseaseHasTreatmentResource($diseaseHasTreatment));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(DiseaseHasTreatment $diseaseHasTreatment): Response
    {
        $diseaseHasTreatment->delete();

        return response()->noContent();
    }
}
