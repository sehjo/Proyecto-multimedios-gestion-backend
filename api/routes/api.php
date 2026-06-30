<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../controller/authController.php';
require_once __DIR__ . '/../controller/patientController.php';
require_once __DIR__ . '/../controller/userController.php';
require_once __DIR__ . '/../controller/appointmentController.php';
require_once __DIR__ . '/../controller/guestAppointmentController.php';
require_once __DIR__ . '/../controller/companionController.php';
require_once __DIR__ . '/../controller/roleController.php';
require_once __DIR__ . '/../controller/permissionController.php';
require_once __DIR__ . '/../controller/notificationController.php';
require_once __DIR__ . '/../controller/availabilityController.php';
require_once __DIR__ . '/../controller/blockController.php';
require_once __DIR__ . '/../controller/institutionLogController.php';
require_once __DIR__ . '/../controller/statsController.php';

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Build a clean URI
$uri = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
if (!$uri && isset($_SERVER['REQUEST_URI'])) {
    $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}

// Normalize: find "v1" or "v2" and work from that segment onward
$seg   = explode('/', $uri);
$v1pos = array_search('v1', $seg);
$v2pos = array_search('v2', $seg);

$version = 'v1';
if ($v2pos !== false) {
    $version = 'v2';
    $seg = array_values(array_slice($seg, $v2pos + 1));
} elseif ($v1pos !== false) {
    $seg = array_values(array_slice($seg, $v1pos + 1));
} else {
    // No version prefix — take from the first non-empty segment
    $seg = array_values(array_filter($seg, fn($s) => $s !== ''));
}

// seg[0]=recurso  seg[1]=id  seg[2]=subrecurso  seg[3]=subparam
$recurso  = $seg[0] ?? '';
$id       = isset($seg[1]) && $seg[1] !== '' ? $seg[1] : null;
$subRec   = $seg[2] ?? null;
$subParam = $seg[3] ?? null;

// =========================================================================
// AUTH  /v1/auth/*
// =========================================================================
if ($recurso === 'auth') {
    $ctrl = new AuthController();
    match (true) {
        $id === 'login'           && $method === 'POST' => $ctrl->login(),
        $id === 'logout'          && $method === 'POST' => $ctrl->logout(),
        $id === 'refresh'         && $method === 'POST' => $ctrl->refresh(),
        $id === 'forgot-password' && $method === 'POST' => $ctrl->forgotPassword(),
        $id === 'reset-password'  && $method === 'POST' => $ctrl->resetPassword(),
        $id === 'reset-password'  && $method === 'GET'  => $ctrl->resetPassword(),
        $id === 'verify-email'    && $method === 'GET'  => $ctrl->verifyEmail(),
        $id === 'has-permission'  && $method === 'GET'  => $ctrl->hasPermission(),
        default => jsonResponse('error', 'Endpoint de autenticación no encontrado.', null, null, null, 404),
    };
    exit;
}

// =========================================================================
// PATIENTS  /v1/patients[/{id}]   +   /v1/logs/patients
// =========================================================================
if ($recurso === 'patients') {
    $ctrl = new PatientController();

    // /patients/logs  and  /patients/logs/{id}  (before the match to avoid parseNumericId('logs'))
    if ($id === 'logs' && $method === 'GET') { $ctrl->logs(); exit; }

    match (true) {
        $id === null && $method === 'GET'                      => $ctrl->index(),
        $id === null && $method === 'POST'                     => $ctrl->store(),
        $id !== null && $method === 'GET' && $subRec === null  => $ctrl->show(parseNumericId($id)),
        $id !== null && in_array($method, ['PUT','PATCH'])     => $ctrl->update(parseNumericId($id)),
        $id !== null && $method === 'DELETE'                   => $ctrl->destroy(parseNumericId($id)),
        default => jsonResponse('error', 'Método no permitido.', null, null, null, 405),
    };
    exit;
}

// =========================================================================
// COMPANIONS  /v1/companions[/{id}]
// =========================================================================
if ($recurso === 'companions') {
    $ctrl = new CompanionController();
    match (true) {
        $id === null && $method === 'GET'                  => $ctrl->index(),
        $id === null && $method === 'POST'                 => $ctrl->store(),
        $id !== null && $method === 'GET'                  => $ctrl->show(parseNumericId($id)),
        $id !== null && in_array($method, ['PUT','PATCH']) => $ctrl->update(parseNumericId($id)),
        $id !== null && $method === 'DELETE'               => $ctrl->destroy(parseNumericId($id)),
        default => jsonResponse('error', 'Método no permitido.', null, null, null, 405),
    };
    exit;
}

// =========================================================================
// APPOINTMENTS  /v1/appointments[/{id}[/change-status|confirm|logs[/{id}]]]
//               + /v1/appointments/calendar
//               + /v1/appointments/{id}/confirm-attendance?token=...  (public, requires single-use token)
// =========================================================================
if ($recurso === 'appointments') {
    $ctrl = new AppointmentController();

    // /appointments/calendar
    if ($id === 'calendar' && $method === 'GET') { $ctrl->calendar(); exit; }

    // /appointments/logs  y  /appointments/logs/{id}
    if ($id === 'logs' && $method === 'GET' && $subRec === null)  { $ctrl->logs(); exit; }
    if ($id === 'logs' && $method === 'GET' && $subRec !== null)  { $ctrl->showLog(parseNumericId($subRec)); exit; }

    if ($id !== null && $subRec === 'change-status'      && $method === 'PATCH') { $ctrl->changeStatus(parseNumericId($id)); exit; }
    if ($id !== null && $subRec === 'confirm'            && $method === 'PATCH') { $ctrl->confirm(parseNumericId($id)); exit; }
    if ($id !== null && $subRec === 'confirm-attendance' && $method === 'GET')   { $ctrl->confirmAttendance(parseNumericId($id)); exit; }

    match (true) {
        $id === null && $method === 'GET'                  => $ctrl->index(),
        $id === null && $method === 'POST'                 => $ctrl->store(),
        $id !== null && $method === 'GET'                  => $ctrl->show(parseNumericId($id)),
        $id !== null && in_array($method, ['PUT','PATCH']) => $ctrl->update(parseNumericId($id)),
        $id !== null && $method === 'DELETE'               => $ctrl->destroy(parseNumericId($id)),
        default => jsonResponse('error', 'Método no permitido.', null, null, null, 405),
    };
    exit;
}

// =========================================================================
// GUEST APPOINTMENTS  /v1/guest-appointments[/start]
// =========================================================================
if ($recurso === 'guest-appointments') {
    $ctrl = new GuestAppointmentController();
    match (true) {
        $id === 'start' && $method === 'POST' => $ctrl->start(),
        $id === null    && $method === 'POST' => $ctrl->store(),
        default => jsonResponse('error', 'Método no permitido.', null, null, null, 405),
    };
    exit;
}

// =========================================================================
// USERS  /v1/users[/register|logs|{id}[/status|roles/{role}|permissions/{perm}]]
// =========================================================================
if ($recurso === 'users') {
    $ctrl = new UserController();

    // /users/register  (public)
    if ($id === 'register' && $method === 'POST') { $ctrl->register(); exit; }

    if ($id === 'logs' && $method === 'GET' && $subRec === null)  { $ctrl->logs(); exit; }
    if ($id === 'logs' && $method === 'GET' && $subRec !== null)  { $ctrl->showLog(parseNumericId($subRec)); exit; }

    if ($id !== null && $subRec === 'status'      && $method === 'PATCH'  && $subParam === null) { $ctrl->changeStatus(parseNumericId($id)); exit; }
    if ($id !== null && $subRec === 'roles'       && $subParam === null   && $method === 'POST')   { $ctrl->assignRole(parseNumericId($id)); exit; }
    if ($id !== null && $subRec === 'roles'       && $subParam !== null   && $method === 'DELETE') { $ctrl->revokeRole(parseNumericId($id), $subParam); exit; }
    if ($id !== null && $subRec === 'permissions' && $subParam === null   && $method === 'POST')   { $ctrl->assignPermission(parseNumericId($id)); exit; }
    if ($id !== null && $subRec === 'permissions' && $subParam !== null   && $method === 'DELETE') { $ctrl->revokePermission(parseNumericId($id), $subParam); exit; }

    match (true) {
        $id === null && $method === 'GET'                      => $ctrl->index(),
        $id === null && $method === 'POST'                     => $ctrl->store(),
        $id !== null && $method === 'GET' && $subRec === null  => $ctrl->show(parseNumericId($id)),
        $id !== null && $method === 'PUT' && $subRec === null  => $ctrl->update(parseNumericId($id)),
        default => jsonResponse('error', 'Método no permitido.', null, null, null, 405),
    };
    exit;
}

// =========================================================================
// ROLES  /v1/roles[/{id}]
// =========================================================================
if ($recurso === 'roles') {
    $ctrl = new RoleController();

    if ($id === 'logs' && $method === 'GET') { $ctrl->logs(); exit; }

    match (true) {
        $id === null && $method === 'GET'                  => $ctrl->index(),
        $id === null && $method === 'POST'                 => $ctrl->store(),
        $id !== null && in_array($method, ['PUT','PATCH']) => $ctrl->update(parseNumericId($id)),
        $id !== null && $method === 'DELETE'               => $ctrl->destroy(parseNumericId($id)),
        default => jsonResponse('error', 'Método no permitido.', null, null, null, 405),
    };
    exit;
}

// =========================================================================
// PERMISSIONS  /v1/permissions
// =========================================================================
if ($recurso === 'permissions') {
    if ($method === 'GET') {
        (new PermissionController())->index();
    } else {
        jsonResponse('error', 'Método no permitido.', null, null, null, 405);
    }
    exit;
}

// =========================================================================
// NOTIFICATIONS  /v1/notifications[/unread|unread-count|read-all|{id}/read|{id}/unread]
// =========================================================================
if ($recurso === 'notifications') {
    $ctrl = new NotificationController();

    // /notifications/unread-count
    if ($id === 'unread-count' && $method === 'GET') { $ctrl->countUnread(); exit; }

    // /notifications/unread
    if ($id === 'unread' && $method === 'GET') { $ctrl->unread(); exit; }

    // /notifications/read-all
    if ($id === 'read-all' && $method === 'PATCH') { $ctrl->markAllAsRead(); exit; }

    if ($id !== null && $subRec === 'read'   && $method === 'PATCH') { $ctrl->markAsRead(parseNumericId($id));   exit; }
    if ($id !== null && $subRec === 'unread' && $method === 'PATCH') { $ctrl->markAsUnread(parseNumericId($id)); exit; }

    match (true) {
        $id === null && $method === 'GET'    => $ctrl->index(),
        $id !== null && $method === 'DELETE' => $ctrl->destroy(parseNumericId($id)),
        default => jsonResponse('error', 'Método no permitido.', null, null, null, 405),
    };
    exit;
}

// =========================================================================
// INSTITUTION AVAILABILITY  /v1/institution-availability[/{id}]
// =========================================================================
if ($recurso === 'institution-availability') {
    $ctrl = new AvailabilityController();
    match (true) {
        $id === null && $method === 'GET'                  => $ctrl->index(),
        $id === null && $method === 'POST'                 => $ctrl->store(),
        $id !== null && $method === 'GET'                  => $ctrl->show(parseNumericId($id)),
        $id !== null && in_array($method, ['PUT','PATCH']) => $ctrl->update(parseNumericId($id)),
        $id !== null && $method === 'DELETE'               => $ctrl->destroy(parseNumericId($id)),
        default => jsonResponse('error', 'Método no permitido.', null, null, null, 405),
    };
    exit;
}

// =========================================================================
// INSTITUTION BLOCKS  /v1/institution-blocks[/{id}]
// =========================================================================
if ($recurso === 'institution-blocks') {
    $ctrl = new BlockController();
    match (true) {
        $id === null && $method === 'GET'                  => $ctrl->index(),
        $id === null && $method === 'POST'                 => $ctrl->store(),
        $id !== null && $method === 'GET'                  => $ctrl->show(parseNumericId($id)),
        $id !== null && in_array($method, ['PUT','PATCH']) => $ctrl->update(parseNumericId($id)),
        $id !== null && $method === 'DELETE'               => $ctrl->destroy(parseNumericId($id)),
        default => jsonResponse('error', 'Método no permitido.', null, null, null, 405),
    };
    exit;
}

// =========================================================================
// INSTITUTION LOGS  /v1/institution-logs[/{id}]
// =========================================================================
if ($recurso === 'institution-logs') {
    $ctrl = new InstitutionLogController();
    match (true) {
        $id === null && $method === 'GET' => $ctrl->index(),
        $id !== null && $method === 'GET' => $ctrl->show(parseNumericId($id)),
        default => jsonResponse('error', 'Método no permitido.', null, null, null, 405),
    };
    exit;
}

// =========================================================================
// STATS  /v1/stats/{entidad}/{metrica}
// =========================================================================
if ($recurso === 'stats') {
    $ctrl    = new StatsController();
    $entidad = $id;
    $metrica = $subRec;

    if ($method !== 'GET') {
        jsonResponse('error', 'Método no permitido.', null, null, null, 405);
    }

    match (true) {
        $entidad === 'patients'     && $metrica === 'total'         => $ctrl->totalPatients(),
        $entidad === 'patients'     && $metrica === 'minors'        => $ctrl->minorPatients(),
        $entidad === 'patients'     && $metrica === 'by-type'       => $ctrl->patientsByType(),
        $entidad === 'patients'     && $metrica === 'by-period'     => $ctrl->patientsByPeriod(),
        $entidad === 'appointments' && $metrica === 'by-status'     => $ctrl->appointmentsByStatus(),
        $entidad === 'appointments' && $metrica === 'by-area'       => $ctrl->appointmentsByArea(),
        $entidad === 'appointments' && $metrica === 'by-period'     => $ctrl->appointmentsByPeriod(),
        $entidad === 'appointments' && $metrica === 'cancellations' => $ctrl->cancellations(),
        $entidad === 'users'        && $metrica === 'status'        => $ctrl->usersByStatus(),
        $entidad === 'users'        && $metrica === 'by-role'       => $ctrl->usersByRole(),
        default => jsonResponse('error', 'Estadística no encontrada.', null, null, null, 404),
    };
    exit;
}

// =========================================================================
// LOGS  /v1/logs/{entidad}[/{id}]
// =========================================================================
if ($recurso === 'logs') {
    $entidad  = $id;
    $logId    = $subRec;

    if ($method !== 'GET') {
        jsonResponse('error', 'Método no permitido.', null, null, null, 405);
    }

    if ($entidad === 'patients') {
        $ctrl = new PatientController();
        $ctrl->logs();
        exit;
    }
    if ($entidad === 'appointments') {
        $ctrl = new AppointmentController();
        $logId !== null ? $ctrl->showLog(parseNumericId($logId)) : $ctrl->logs();
        exit;
    }
    if ($entidad === 'users') {
        $ctrl = new UserController();
        $logId !== null ? $ctrl->showLog(parseNumericId($logId)) : $ctrl->logs();
        exit;
    }

    jsonResponse('error', 'Recurso de logs no encontrado.', null, null, null, 404);
}

// =========================================================================
// 404 por defecto
// =========================================================================
jsonResponse('error', 'Ruta no encontrada.', null, null, null, 404);
