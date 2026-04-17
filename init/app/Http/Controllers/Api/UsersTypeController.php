<?php

namespace App\Http\Controllers\Api;

use App\Models\UsersType;
use Illuminate\Http\Request;
use App\Http\Requests\UsersTypeRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UsersTypeResource;

class UsersTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $usersTypes = UsersType::paginate();

        return UsersTypeResource::collection($usersTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UsersTypeRequest $request): JsonResponse
    {
        $usersType = UsersType::create($request->validated());

        return response()->json(new UsersTypeResource($usersType));
    }

    /**
     * Display the specified resource.
     */
    public function show(UsersType $usersType): JsonResponse
    {
        return response()->json(new UsersTypeResource($usersType));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UsersTypeRequest $request, UsersType $usersType): JsonResponse
    {
        $usersType->update($request->validated());

        return response()->json(new UsersTypeResource($usersType));
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(UsersType $usersType): Response
    {
        $usersType->delete();

        return response()->noContent();
    }
}
