<?php

class Env
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) return;

        $path = dirname(__DIR__, 2) . '/.env';
        if (!file_exists($path)) return;

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            // Strip optional surrounding quotes from the value
            $value = trim($value, '"\'');
            if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, string $default = ''): string
    {
        self::load();
        $val = getenv($key);
        return ($val !== false && $val !== '') ? $val : $default;
    }
}
