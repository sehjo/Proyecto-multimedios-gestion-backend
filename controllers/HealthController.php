<?php

class HealthController
{
    public function index(Request $request): void
    {
        Response::json(['status' => 'ok']);
    }
}
