<?php

require_once __DIR__ . '/../core/Env.php';

function config(?string $key = null, $default = null)
{
    static $config = null;

    if ($config === null) {
        env_load(__DIR__ . '/../.env');

        $config = [
            'app' => [
                'env' => env('APP_ENV', 'local'),
                'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
                'url' => rtrim(env('APP_URL', 'http://localhost'), '/'),
                'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'),
            ],
            'database' => [
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'db_ccss'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
            ],
            'mail' => [
                'mailer' => env('MAIL_MAILER', 'log'),
                'host' => env('MAIL_HOST', '127.0.0.1'),
                'port' => env('MAIL_PORT', '2525'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'from_name' => env('MAIL_FROM_NAME', 'API'),
                'log_path' => __DIR__ . '/../storage/logs/mail.log',
            ],
            'auth' => [
                'password_reset_expire_minutes' => 60,
            ],
        ];
    }

    if ($key === null) {
        return $config;
    }

    $value = $config;

    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}
