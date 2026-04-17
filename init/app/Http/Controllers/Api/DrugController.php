<?php

namespace App\Http\Controllers\Api;

use App\Models\Drug;
use Illuminate\Http\Request;
use App\Http\Requests\DrugRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\DrugResource;

class DrugController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $drugs = Drug::paginate();

        return DrugResource::collection($drugs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DrugRequest $request): JsonResponse
    {
        $drug = Drug::create($request->validated());

        return response()->json(new DrugResource($drug));
    }

    /**
     * Display the specified resource.
     */
    public function show(Drug $drug): JsonResponse
    {
        return response()->json(new DrugResource($drug));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DrugRequest $request, Drug $drug): JsonResponse
    {
        $drug->update($request->validated());

        return response()->json(new DrugResource($drug));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Drug $drug): Response
    {
        $drug->delete();

        return response()->noContent();
    }
}
