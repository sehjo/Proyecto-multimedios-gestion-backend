<?php
// Silence PHP errors so they never contaminate the JSON response.
ini_set('display_errors', '0');
ini_set('html_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/api/routes/api.php';
