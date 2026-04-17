<?php

namespace App\Http\Controllers\Api;

use App\Models\Diagnosis;
use Illuminate\Http\Request;
use App\Http\Requests\DiagnosisRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\DiagnosisResource;

class DiagnosisController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $diagnoses = Diagnosis::with(['patient', 'disease', 'user'])->paginate();

        return DiagnosisResource::collection($diagnoses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DiagnosisRequest $request): JsonResponse
    {
        $diagnosis = Diagnosis::create($request->validated());

        return response()->json(new DiagnosisResource($diagnosis));
    }

    /**
     * Display the specified resource.
     */
    public function show(Diagnosis $diagnosis): JsonResponse
    {
        $diagnosis->loadMissing(['patient', 'disease', 'user']);

        return response()->json(new DiagnosisResource($diagnosis));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DiagnosisRequest $request, Diagnosis $diagnosis): JsonResponse
    {
        $diagnosis->update($request->validated());

        return response()->json(new DiagnosisResource($diagnosis));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Diagnosis $diagnosis): Response
    {
        $diagnosis->delete();

        return response()->noContent();
    }
}
