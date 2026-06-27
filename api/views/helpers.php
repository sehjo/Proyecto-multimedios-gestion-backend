<?php

function jsonResponse(string $status, string $message, $data = null, $errors = null, $meta = null, int $httpCode = 200)
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status"  => $status,
        "message" => $message,
        "data"    => $data,
        "errors"  => $errors,
        "meta"    => $meta,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(
            'error',
            'JSON inválido en el cuerpo de la petición.',
            null,
            ['body' => [json_last_error_msg()]],
            null,
            400
        );
    }

    return is_array($decoded) ? $decoded : [];
}

function validar(array $datos, array $reglas): array
{
    $errors = [];

    foreach ($reglas as $campo => $reglasStr) {
        $lista = explode('|', $reglasStr);
        $valor = isset($datos[$campo]) ? $datos[$campo] : null;
        $esOpcional = in_array('nullable', $lista, true);

        foreach ($lista as $regla) {
            // required
            if ($regla === 'required' && ($valor === null || $valor === '')) {
                $errors[$campo][] = "El campo {$campo} es obligatorio.";
                break;
            }

            // skip remaining rules if value is empty and field is nullable
            if ($esOpcional && ($valor === null || $valor === '')) {
                break;
            }

            // max:N
            if (strncmp($regla, 'max:', 4) === 0) {
                $max = (int) substr($regla, 4);
                if (strlen((string) $valor) > $max) {
                    $errors[$campo][] = "El campo {$campo} no debe exceder {$max} caracteres.";
                }
            }

            // min:N
            if (strncmp($regla, 'min:', 4) === 0) {
                $min = (int) substr($regla, 4);
                if (strlen((string) $valor) < $min) {
                    $errors[$campo][] = "El campo {$campo} debe tener al menos {$min} caracteres.";
                }
            }

            // email
            if ($regla === 'email' && $valor !== null && $valor !== '') {
                if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    $errors[$campo][] = "El campo {$campo} debe ser un correo electrónico válido.";
                }
            }

            // integer
            if ($regla === 'integer' && $valor !== null && $valor !== '') {
                if (!ctype_digit((string) $valor) && !is_int($valor)) {
                    $errors[$campo][] = "El campo {$campo} debe ser un número entero.";
                }
            }

            // date (Y-m-d)
            if ($regla === 'date' && $valor !== null && $valor !== '') {
                $d = DateTime::createFromFormat('Y-m-d', $valor);
                if (!$d || $d->format('Y-m-d') !== $valor) {
                    $errors[$campo][] = "El campo {$campo} debe ser una fecha válida (YYYY-MM-DD).";
                }
            }

            // before_today
            if ($regla === 'before_today' && $valor !== null && $valor !== '') {
                if (strtotime($valor) >= strtotime(date('Y-m-d'))) {
                    $errors[$campo][] = "El campo {$campo} debe ser una fecha anterior a hoy.";
                }
            }

            // after_now
            if ($regla === 'after_now' && $valor !== null && $valor !== '') {
                if (strtotime($valor) <= time()) {
                    $errors[$campo][] = "El campo {$campo} debe ser una fecha y hora futura.";
                }
            }

            // regex:/<pattern>/
            if (strncmp($regla, 'regex:', 6) === 0 && $valor !== null && $valor !== '') {
                $patron = substr($regla, 6);
                if (!preg_match($patron, (string) $valor)) {
                    $errors[$campo][] = "El campo {$campo} tiene un formato inválido.";
                }
            }

            // in:val1,val2,...
            if (strncmp($regla, 'in:', 3) === 0 && $valor !== null && $valor !== '') {
                $opciones = explode(',', substr($regla, 3));
                if (!in_array($valor, $opciones, true)) {
                    $lista_str = implode(', ', $opciones);
                    $errors[$campo][] = "El campo {$campo} debe ser uno de: {$lista_str}.";
                }
            }

            // password_strength
            if ($regla === 'password_strength' && $valor !== null && $valor !== '') {
                if (!preg_match('/[A-Z]/', $valor) || !preg_match('/[a-z]/', $valor) || !preg_match('/[0-9]/', $valor)) {
                    $errors[$campo][] = "La contraseña debe contener al menos una mayúscula, una minúscula y un número.";
                }
            }
        }
    }

    return $errors;
}

function parseNumericId(?string $raw): int
{
    if ($raw === null || !ctype_digit($raw) || (int) $raw <= 0) {
        jsonResponse('error', 'El ID debe ser un número entero positivo.', null, null, null, 400);
    }
    return (int) $raw;
}

function getAuthUser()
{
    // Look up the Authorization header case-insensitively to support mod_php and PHP-FPM
    $headers    = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : [];
    $authHeader = $headers['authorization']
        ?? $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (!$authHeader || strncmp($authHeader, 'Bearer ', 7) !== 0) {
        return null;
    }

    $token = substr($authHeader, 7);

    require_once __DIR__ . '/../dao/authDao.php';
    $authDao = new AuthDao();
    return $authDao->findUserByToken($token);
}

function requireAuth()
{
    $user = getAuthUser();
    if (!$user) {
        jsonResponse('error', 'No autenticado. Proporciona un token válido.', null, null, null, 401);
    }
    return $user;
}
