<?php
/**
 * Smoke test suite for the API.
 *
 * Hits the running dev server over HTTP and checks basic CRUD, validation,
 * and relationship behavior for the main resources. Not exhaustive — one
 * happy path + a couple of validation/relation checks per resource.
 *
 * Usage:
 *   1. cp .env.example .env  (and adjust DB credentials)
 *   2. mysql ... db_ccss < api/config/schema.sql
 *   3. php -S localhost:8000 index.php
 *   4. php tests/api_smoke_test.php   (in another terminal)
 *
 * The suite reseeds the database (api/config/seed.sql) before every run using
 * the same DB credentials as the app (.env), since it creates fixed rows
 * (identifiers, emails) that must not already exist. Set SKIP_SEED=1 to skip
 * this and reuse whatever data is already in the database.
 *
 * Optional: set API_BASE_URL env var to point at a different host.
 */

if (getenv('SKIP_SEED') !== '1') {
    reseedDatabase();
}

function reseedDatabase(): void
{
    require_once __DIR__ . '/../api/config/connection.php';

    $pdo = (new Connection())->connect();
    $sql = file_get_contents(__DIR__ . '/../api/config/seed.sql');
    $pdo->exec($sql);
    echo "Database reseeded.\n";
}

$baseUrl = getenv('API_BASE_URL') ?: 'http://localhost:8000/api/v1';

$pass = 0;
$fail = 0;
$failures = [];

function request(string $method, string $path, ?array $body = null, ?string $token = null): array
{
    global $baseUrl;

    $ch = curl_init($baseUrl . $path);
    $headers = ['Content-Type: application/json', 'X-Requested-With: XMLHttpRequest'];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $raw  = curl_exec($ch);
    if ($raw === false) {
        fwrite(STDERR, "cURL error on {$method} {$path}: " . curl_error($ch) . "\n");
        fwrite(STDERR, "Is the server running? See usage instructions at the top of this file.\n");
        exit(1);
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($raw, true);
    return [$code, $json];
}

function check(string $label, bool $condition, $context = null): void
{
    global $pass, $fail, $failures;
    if ($condition) {
        $pass++;
        echo "  [PASS] {$label}\n";
    } else {
        $fail++;
        $failures[] = $label;
        echo "  [FAIL] {$label}\n";
        if ($context !== null) {
            echo '         ' . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}

function section(string $title): void
{
    echo "\n== {$title} ==\n";
}

function futureDatetime(int $days): string
{
    return date('Y-m-d H:i:s', strtotime("+{$days} days"));
}

// =============================================================================
// AUTH
// =============================================================================
section('Auth');

[$code, $body] = request('POST', '/auth/login', ['email' => 'admin@ucr.ac.cr', 'password' => 'wrong-password']);
check('login with wrong password -> 401', $code === 401, $body);

[$code, $body] = request('POST', '/auth/login', ['email' => 'not-an-email']);
check('login missing password -> 422', $code === 422 && isset($body['errors']['password']), $body);

[$code, $body] = request('POST', '/auth/login', ['email' => 'admin@ucr.ac.cr', 'password' => 'Password123!']);
check('login admin -> 200 with token', $code === 200 && !empty($body['data']['access_token']), $body);
$adminToken = $body['data']['access_token'] ?? null;

[$code, $body] = request('POST', '/auth/login', ['email' => 'doctor@ucr.ac.cr', 'password' => 'Password123!']);
check('login doctor -> 200', $code === 200, $body);
$doctorToken = $body['data']['access_token'] ?? null;

[$code, $body] = request('POST', '/auth/login', ['email' => 'maria.garcia@ucr.ac.cr', 'password' => 'Password123!']);
check('login patient (limited perms) -> 200', $code === 200, $body);
$patientToken = $body['data']['access_token'] ?? null;

[$code, $body] = request('POST', '/auth/login', ['email' => 'diego.torres@ucr.ac.cr', 'password' => 'Password123!']);
check('login inactive user -> 403', $code === 403, $body);

[$code, $body] = request('GET', '/patients', null, null);
check('request without token -> 401', $code === 401, $body);

// =============================================================================
// PATIENTS  (CRUD + validation + FK relation guard)
// =============================================================================
section('Patients');

[$code, $body] = request('POST', '/patients', ['full_name' => 'Test Patient'], $adminToken);
check('create patient missing fields -> 422', $code === 422 && isset($body['errors']['identifier']), $body);

[$code, $body] = request('POST', '/patients', [
    'full_name'  => 'Test Patient',
    'identifier' => 'not-valid-format',
    'email'      => 'test.patient@ucr.ac.cr',
    'birth_date' => '2000-01-01',
    'user_type'  => 'Estudiante',
    'phone'      => '8888-8888',
], $adminToken);
check('create patient invalid identifier format -> 422', $code === 422 && isset($body['errors']['identifier']), $body);

$newPatient = [
    'full_name'  => 'Smoke Test Patient',
    'identifier' => '9-9999-0001',
    'email'      => 'smoke.patient@ucr.ac.cr',
    'birth_date' => '1999-06-15',
    'user_type'  => 'Estudiante',
    'phone'      => '8899-0001',
];
[$code, $body] = request('POST', '/patients', $newPatient, $adminToken);
check('create patient -> 201', $code === 201 && !empty($body['data']['id']), $body);
$patientId = $body['data']['id'] ?? null;

[$code, $body] = request('POST', '/patients', $newPatient, $adminToken);
check('create patient duplicate identifier -> 422', $code === 422 && isset($body['errors']['identifier']), $body);

[$code, $body] = request('GET', "/patients/{$patientId}", null, $adminToken);
check('show patient -> 200 stores data correctly', $code === 200 && $body['data']['email'] === $newPatient['email'], $body);

[$code, $body] = request('PUT', "/patients/{$patientId}", array_merge($newPatient, ['full_name' => 'Smoke Test Patient Updated']), $adminToken);
check('update patient -> 200', $code === 200 && $body['data']['full_name'] === 'Smoke Test Patient Updated', $body);

[$code, $body] = request('GET', "/patients/{$patientId}", null, $adminToken);
check('update persisted on reread', $code === 200 && $body['data']['full_name'] === 'Smoke Test Patient Updated', $body);

[$code, $body] = request('GET', '/patients', null, $patientToken);
check('patient role lacks patients.read -> 403', $code === 403, $body);

// seeded patient id=1 has an appointment attached -> deletion must be blocked by FK
[$code, $body] = request('DELETE', '/patients/1', null, $adminToken);
check('delete patient with appointments -> 409', $code === 409, $body);

// =============================================================================
// COMPANIONS  (CRUD + validation)
// =============================================================================
section('Companions');

[$code, $body] = request('POST', '/companions', ['full_name' => 'No Identifier'], $adminToken);
check('create companion missing fields -> 422', $code === 422, $body);

$newCompanion = ['full_name' => 'Smoke Test Companion', 'identifier' => '9-9999-0002', 'phone' => '8899-0002'];
[$code, $body] = request('POST', '/companions', $newCompanion, $adminToken);
check('create companion -> 201', $code === 201 && !empty($body['data']['id']), $body);
$companionId = $body['data']['id'] ?? null;

[$code, $body] = request('PUT', "/companions/{$companionId}", array_merge($newCompanion, ['full_name' => 'Companion Updated']), $adminToken);
check('update companion -> 200', $code === 200 && $body['data']['full_name'] === 'Companion Updated', $body);

// =============================================================================
// APPOINTMENTS  (CRUD + validation + relations + status flow)
// =============================================================================
section('Appointments');

[$code, $body] = request('POST', '/appointments', [
    'patient_id'           => 999999,
    'attention_area'       => 'cardiology',
    'reason'               => 'Test',
    'appointment_datetime' => futureDatetime(5),
], $adminToken);
check('create appointment nonexistent patient -> 422', $code === 422 && isset($body['errors']['patient_id']), $body);

[$code, $body] = request('POST', '/appointments', [
    'patient_id'           => $patientId,
    'attention_area'       => 'not_a_real_area',
    'reason'               => 'Test',
    'appointment_datetime' => futureDatetime(5),
], $adminToken);
check('create appointment invalid area -> 422', $code === 422 && isset($body['errors']['attention_area']), $body);

[$code, $body] = request('POST', '/appointments', [
    'patient_id'           => $patientId,
    'attention_area'       => 'cardiology',
    'reason'               => 'Test',
    'appointment_datetime' => futureDatetime(-1),
], $adminToken);
check('create appointment past datetime -> 422', $code === 422 && isset($body['errors']['appointment_datetime']), $body);

[$code, $body] = request('POST', '/appointments', [
    'patient_id'           => $patientId,
    'attention_area'       => 'cardiology',
    'reason'               => 'Chequeo de rutina',
    'appointment_datetime' => futureDatetime(10),
], $adminToken);
check('create appointment -> 201', $code === 201 && !empty($body['data']['id']), $body);
$appointmentId = $body['data']['id'] ?? null;

[$code, $body] = request('GET', "/appointments/{$appointmentId}", null, $adminToken);
check(
    'show appointment includes nested patient relation',
    $code === 200 && ($body['data']['patient']['id'] ?? null) == $patientId,
    $body
);

[$code, $body] = request('PATCH', "/appointments/{$appointmentId}/change-status", ['status' => 'rejected'], $adminToken);
check('reject without cancellation_reason -> 422', $code === 422 && isset($body['errors']['cancellation_reason']), $body);

[$code, $body] = request('PATCH', "/appointments/{$appointmentId}/change-status", ['status' => 'approved'], $adminToken);
check('approve appointment -> 200', $code === 200 && $body['data']['status'] === 'approved', $body);

[$code, $body] = request('PATCH', "/appointments/{$appointmentId}/confirm", null, $adminToken);
check('confirm appointment attendance -> 200', $code === 200 && $body['data']['status'] === 'confirmed', $body);

[$code, $body] = request('DELETE', "/appointments/{$appointmentId}", null, $adminToken);
check('delete appointment -> 204', $code === 204);

[$code, $body] = request('GET', "/appointments/{$appointmentId}", null, $adminToken);
check('deleted appointment -> 404', $code === 404, $body);

// =============================================================================
// USERS  (register, roles, status guards)
// =============================================================================
section('Users');

[$code, $body] = request('POST', '/users/register', ['name' => 'Smoke User'], null);
check('public register missing fields -> 422', $code === 422, $body);

$newUser = ['name' => 'Smoke Test User', 'identifier' => 'SMK-001', 'email' => 'smoke.user@ucr.ac.cr', 'password' => 'Password123!'];
[$code, $body] = request('POST', '/users/register', $newUser, null);
check('public register -> 201', $code === 201 && !empty($body['data']['id']), $body);
$registeredUserId = $body['data']['id'] ?? null;

[$code, $body] = request('POST', '/users/register', $newUser, null);
check('duplicate email register -> 422', $code === 422 && isset($body['errors']['email']), $body);

$adminUser = ['name' => 'Admin Created User', 'identifier' => 'SMK-002', 'email' => 'smoke.admin.made@ucr.ac.cr', 'password' => 'Password123!', 'status' => 'active'];
[$code, $body] = request('POST', '/users', $adminUser, $adminToken);
check('admin create user -> 201', $code === 201 && !empty($body['data']['id']), $body);
$createdUserId = $body['data']['id'] ?? null;

[$code, $body] = request('POST', "/users/{$createdUserId}/roles", ['role' => 'doctor'], $adminToken);
check('assign role to user -> 200', $code === 200 && collect_has_role($body['data']['roles'] ?? [], 'doctor'), $body);

[$code, $body] = request('DELETE', "/users/{$createdUserId}/roles/doctor", null, $adminToken);
check('revoke only role -> 409', $code === 409, $body);

[$code, $body] = request('PATCH', '/users/1/status', ['status' => 'inactive'], $adminToken);
check('deactivate last admin -> 409', $code === 409, $body);

[$code, $body] = request('PUT', '/users/1', ['name' => 'Self edit', 'email' => 'admin@ucr.ac.cr'], $adminToken);
check('admin editing own account -> 403', $code === 403, $body);

function collect_has_role(array $roles, string $name): bool
{
    foreach ($roles as $r) {
        if (($r['name'] ?? null) === $name) return true;
    }
    return false;
}

// =============================================================================
// ROLES & PERMISSIONS
// =============================================================================
section('Roles & Permissions');

[$code, $body] = request('GET', '/permissions', null, $adminToken);
check('list permissions -> 200', $code === 200 && is_array($body['data']), $body);

// Roles reference permissions by numeric ID (see permissions.raw in the Postman
// collection), not by name — build a name -> id lookup from the list above.
$permByName = [];
foreach ($body['data'] ?? [] as $p) {
    $permByName[$p['name']] = (int) $p['id'];
}

[$code, $body] = request('POST', '/roles', ['name' => 'smoke-role'], $adminToken);
check('create role without permissions -> 422', $code === 422 && isset($body['errors']['permissions']), $body);

[$code, $body] = request('POST', '/roles', ['name' => 'smoke-role', 'permissions' => [$permByName['patients.read']]], $adminToken);
check('create role -> 201', $code === 201 && count($body['data']['permissions']) === 1, $body);
$roleId = $body['data']['id'] ?? null;

[$code, $body] = request('PUT', "/roles/{$roleId}", ['permissions' => [$permByName['patients.read'], $permByName['patients.create']]], $adminToken);
check('update role permissions -> 200', $code === 200 && count($body['data']['permissions']) === 2, $body);

// admin's own role (id=1) must not be editable by the admin
[$code, $body] = request('PUT', '/roles/1', ['name' => 'admin-renamed'], $adminToken);
check('admin cannot modify own role -> 403', $code === 403, $body);

[$code, $body] = request('DELETE', "/roles/{$roleId}", null, $adminToken);
check('delete role -> 204', $code === 204);

// =============================================================================
// INSTITUTION AVAILABILITY  (CRUD + overlap validation)
// =============================================================================
section('Institution availability');

[$code, $body] = request('POST', '/institution-availability', ['day_of_week' => 'monday', 'start_time' => '08:00', 'end_time' => '17:00'], $adminToken);
check('create availability slot -> 201', $code === 201 && !empty($body['data']['id']), $body);
$slotId = $body['data']['id'] ?? null;

[$code, $body] = request('POST', '/institution-availability', ['day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '10:00'], $adminToken);
check('overlapping slot -> 422', $code === 422 && isset($body['errors']['start_time']), $body);

[$code, $body] = request('PUT', "/institution-availability/{$slotId}", ['day_of_week' => 'monday', 'start_time' => '07:00', 'end_time' => '18:00'], $adminToken);
check('update availability slot -> 200', $code === 200, $body);

[$code, $body] = request('DELETE', "/institution-availability/{$slotId}", null, $adminToken);
check('delete availability slot -> 204', $code === 204);

// =============================================================================
// INSTITUTION BLOCKS  (CRUD + validation)
// =============================================================================
section('Institution blocks');

$blockDate = date('Y-m-d', strtotime('+40 days'));
[$code, $body] = request('POST', '/institution-blocks', ['date' => $blockDate, 'full_day' => true, 'reason' => 'Smoke test block'], $adminToken);
check('create full-day block -> 201', $code === 201 && !empty($body['data']['id']), $body);
$blockId = $body['data']['id'] ?? null;

[$code, $body] = request('POST', '/institution-blocks', ['date' => $blockDate, 'full_day' => true], $adminToken);
check('duplicate block date -> 422', $code === 422 && isset($body['errors']['date']), $body);

[$code, $body] = request('POST', '/institution-blocks', ['date' => date('Y-m-d', strtotime('-1 day')), 'full_day' => true], $adminToken);
check('block in the past -> 422', $code === 422 && isset($body['errors']['date']), $body);

[$code, $body] = request('DELETE', "/institution-blocks/{$blockId}", null, $adminToken);
check('delete block -> 204', $code === 204);

// =============================================================================
// NOTIFICATIONS  (report/list)
// =============================================================================
section('Notifications');

[$code, $body] = request('GET', '/notifications', null, $adminToken);
check('list own notifications -> 200', $code === 200, $body);

[$code, $body] = request('GET', '/notifications/unread-count', null, $adminToken);
check('unread count -> 200', $code === 200 && isset($body['data']['count']), $body);

[$code, $body] = request('PATCH', '/notifications/read-all', null, $adminToken);
check('mark all notifications read -> 200', $code === 200, $body);

// =============================================================================
// STATS  (report endpoints)
// =============================================================================
section('Stats');

[$code, $body] = request('GET', '/stats/patients/total', null, $adminToken);
check('stats: total patients -> 200', $code === 200, $body);

[$code, $body] = request('GET', '/stats/patients/by-type', null, $adminToken);
check('stats: patients by type -> 200', $code === 200, $body);

[$code, $body] = request('GET', '/stats/appointments/by-status', null, $adminToken);
check('stats: appointments by status -> 200', $code === 200, $body);

[$code, $body] = request('GET', '/stats/appointments/by-period', null, $adminToken);
check('stats: appointments by period missing params -> 422', $code === 422, $body);

[$code, $body] = request('GET', '/stats/appointments/by-period?date_from=2026-01-01&date_to=2026-01-31&period=day', null, $adminToken);
check('stats: appointments by period -> 200', $code === 200, $body);

[$code, $body] = request('GET', '/stats/users/by-role', null, $adminToken);
check('stats: users by role -> 200', $code === 200, $body);

// =============================================================================
// LOGS  (audit trail read endpoints)
// =============================================================================
section('Logs');

[$code, $body] = request('GET', '/logs/patients', null, $adminToken);
check('patient logs -> 200', $code === 200 && !empty($body['data']), $body);

[$code, $body] = request('GET', '/logs/appointments', null, $adminToken);
check('appointment logs -> 200', $code === 200 && !empty($body['data']), $body);

[$code, $body] = request('GET', '/logs/users', null, $adminToken);
check('user logs -> 200', $code === 200 && !empty($body['data']), $body);

// =============================================================================
// GUEST APPOINTMENTS  (public flow)
// =============================================================================
section('Guest appointments');

[$code, $body] = request('POST', '/guest-appointments/start', ['email' => 'not-institutional@gmail.com']);
check('guest start non-institutional email -> 422', $code === 422 && isset($body['errors']['email']), $body);

[$code, $body] = request('POST', '/guest-appointments/start', ['email' => 'guest.smoke@ucr.ac.cr']);
check('guest start -> 200 with verification token', $code === 200 && !empty($body['data']['verification_token']), $body);
$verificationToken = $body['data']['verification_token'] ?? null;

// The verification JWT is sent as a Bearer token (as the frontend does), not in the body.
[$code, $body] = request('POST', '/guest-appointments', [
    'full_name'            => 'Guest Smoke Patient',
    'identifier'           => '9-9999-0003',
    'birth_date'           => '1997-03-10',
    'user_type'            => 'Estudiante',
    'phone'                => '8899-0003',
    'attention_area'       => 'general_medicine',
    'reason'               => 'Consulta general',
    'appointment_datetime' => futureDatetime(6),
], $verificationToken);
check('guest appointment creation -> 201', $code === 201 && !empty($body['data']['id']), $body);

[$code, $body] = request('POST', '/guest-appointments', ['full_name' => 'No Token']);
check('guest appointment without valid token -> 401', $code === 401, $body);

[$code, $body] = request('POST', '/guest-appointments', [], $verificationToken);
check('guest appointment valid token but missing fields -> 422', $code === 422, $body);

// =============================================================================
// SUMMARY
// =============================================================================
echo "\n============================================\n";
echo "Total: " . ($pass + $fail) . "  Passed: {$pass}  Failed: {$fail}\n";
if ($fail > 0) {
    echo "Failed checks:\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
}
echo "============================================\n";

exit($fail > 0 ? 1 : 0);
