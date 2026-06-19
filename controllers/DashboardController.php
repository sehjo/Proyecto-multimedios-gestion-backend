<?php

class DashboardController
{
    public function index(Request $request): void
    {
        $user = Auth::user($request);

        if (!$user) {
            Response::json(['message' => 'Unauthenticated.'], 401);

            return;
        }

        Response::json([
            'total_patients' => PatientRepository::count(),
            'total_users' => UserRepository::count(),
        ]);
    }
}
