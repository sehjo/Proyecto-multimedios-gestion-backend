<?php

namespace App\Http\Controllers\Api;

use App\Models\DiagnosesHasTreatment;
use Illuminate\Http\Request;
use App\Http\Requests\DiagnosesHasTreatmentRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\DiagnosesHasTreatmentResource;

class DiagnosesHasTreatmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $diagnosesHasTreatments = DiagnosesHasTreatment::with(['drug', 'diagnosis'])->paginate();

        return DiagnosesHasTreatmentResource::collection($diagnosesHasTreatments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DiagnosesHasTreatmentRequest $request): JsonResponse
    {
        $diagnosesHasTreatment = DiagnosesHasTreatment::create($request->validated());

        return response()->json(new DiagnosesHasTreatmentResource($diagnosesHasTreatment));
    }

    /**
     * Display the specified resource.
     */
    public function show(DiagnosesHasTreatment $diagnosesHasTreatment): JsonResponse
    {
        $diagnosesHasTreatment->loadMissing(['drug', 'diagnosis']);

        return response()->json(new DiagnosesHasTreatmentResource($diagnosesHasTreatment));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DiagnosesHasTreatmentRequest $request, DiagnosesHasTreatment $diagnosesHasTreatment): JsonResponse
    {
        $diagnosesHasTreatment->update($request->validated());

        return response()->json(new DiagnosesHasTreatmentResource($diagnosesHasTreatment));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(DiagnosesHasTreatment $diagnosesHasTreatment): Response
    {
        $diagnosesHasTreatment->delete();

        return response()->noContent();
    }
}
