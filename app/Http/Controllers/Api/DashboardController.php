<?php

namespace App\Http\Controllers\Api;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'total_patients' => Patient::count(),
            'total_users'    => User::count(),
        ]);
    }
}
