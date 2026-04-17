<?php

namespace App\Http\Controllers\Api;

use App\Models\Priority;
use Illuminate\Http\Request;
use App\Http\Requests\PriorityRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PriorityResource;

class PriorityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $priorities = Priority::paginate();

        return PriorityResource::collection($priorities);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PriorityRequest $request): JsonResponse
    {
        $priority = Priority::create($request->validated());

        return response()->json(new PriorityResource($priority));
    }

    /**
     * Display the specified resource.
     */
    public function show(Priority $priority): JsonResponse
    {
        return response()->json(new PriorityResource($priority));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PriorityRequest $request, Priority $priority): JsonResponse
    {
        $priority->update($request->validated());

        return response()->json(new PriorityResource($priority));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Priority $priority): Response
    {
        $priority->delete();

        return response()->noContent();
    }
}
