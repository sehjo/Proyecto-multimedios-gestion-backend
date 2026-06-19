<?php

require_once __DIR__ . '/../core/Env.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Paginator.php';

$failures = 0;

function check(string $description, bool $condition): void
{
    global $failures;

    if ($condition) {
        echo "PASS: $description\n";
    } else {
        echo "FAIL: $description\n";
        $failures++;
    }
}

// Validator: required field missing.
$errors = Validator::make([], ['name' => ['required', 'string', 'max:255']]);
check('Validator marca "name" como requerido cuando falta', isset($errors['name']));

// Validator: valid data produces no errors.
$errors = Validator::make(
    ['name' => 'Carlos', 'email' => 'carlos@example.com'],
    ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email']]
);
check('Validator no genera errores con datos válidos', empty($errors));

// Validator: invalid email format.
$errors = Validator::make(['email' => 'no-es-un-correo'], ['email' => ['required', 'email']]);
check('Validator detecta un correo con formato inválido', isset($errors['email']));

// Validator: max length violation.
$errors = Validator::make(['nick' => str_repeat('a', 10)], ['nick' => ['required', 'string', 'max:5']]);
check('Validator detecta que un valor excede la longitud máxima', isset($errors['nick']));

// Validator: nullable field with empty value is skipped.
$errors = Validator::make(['suffering' => ''], ['suffering' => ['nullable', 'string', 'max:500']]);
check('Validator permite campos "nullable" vacíos', empty($errors));

// password_hash / password_verify round trip (compatible con los hashes bcrypt usados por el backend).
$hash = password_hash('Admin1234!', PASSWORD_BCRYPT);
check('password_verify valida un hash generado con password_hash', password_verify('Admin1234!', $hash));
check('password_verify rechaza una contraseña incorrecta', !password_verify('otra-clave', $hash));

// Token hashing determinism (igual al usado por Auth::issueToken / Auth::user).
$plainToken = bin2hex(random_bytes(40));
check(
    'hash("sha256", $token) es determinista para el mismo token',
    hash('sha256', $plainToken) === hash('sha256', $plainToken)
);
check(
    'hash("sha256", $token) difiere entre tokens distintos',
    hash('sha256', $plainToken) !== hash('sha256', bin2hex(random_bytes(40)))
);

// Paginator metadata.
$meta = Paginator::meta(45, 2, 20);
check('Paginator calcula last_page correctamente', $meta['last_page'] === 3);
check('Paginator calcula from/to correctamente', $meta['from'] === 21 && $meta['to'] === 40);

echo "\n";

if ($failures > 0) {
    echo "$failures prueba(s) fallaron.\n";
    exit(1);
}

echo "Todas las pruebas pasaron.\n";
exit(0);
