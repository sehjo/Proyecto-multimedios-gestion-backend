<?php

namespace App\Http\Controllers\Api;

use App\Models\DiagnosesHasTreatment;
use App\Models\Patient;
use Illuminate\Http\Request;
use App\Http\Requests\PatientRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $patients = Patient::with(['disease.priority', 'user'])->paginate();

        return PatientResource::collection($patients);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated());

        return response()->json(new PatientResource($patient));
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient): JsonResponse
    {
        $patient->loadMissing(['disease.priority', 'user']);

        return response()->json(new PatientResource($patient));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PatientRequest $request, Patient $patient): JsonResponse
    {
        $patient->update($request->validated());

        return response()->json(new PatientResource($patient));
    }

    /**
     * Delete the specified resource with cascade.
     */
    public function destroy(Patient $patient)
    {
        $diagnosisIds = $patient->diagnoses()->pluck('id');
        DiagnosesHasTreatment::whereIn('diagnoses_id', $diagnosisIds)->delete();
        $patient->diagnoses()->delete();
        $patient->delete();

        return response()->noContent();
    }
}
