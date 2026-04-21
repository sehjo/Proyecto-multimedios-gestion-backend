<?php

namespace App\Http\Controllers\Api;

use App\Models\Patient;
use Illuminate\Http\Request;
use App\Http\Requests\PatientRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $patients = Patient::with(['user'])->paginate();

        return PatientResource::collection($patients);
    }

    public function store(PatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated());

        return response()->json(new PatientResource($patient));
    }

    public function show(Patient $patient): JsonResponse
    {
        $patient->loadMissing(['user']);

        return response()->json(new PatientResource($patient));
    }

    public function update(PatientRequest $request, Patient $patient): JsonResponse
    {
        $patient->update($request->validated());

        return response()->json(new PatientResource($patient));
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return response()->noContent();
    }
}
