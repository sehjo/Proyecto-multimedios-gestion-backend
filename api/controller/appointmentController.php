<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/appointmentDao.php';
require_once __DIR__ . '/../models/appointment.php';
require_once __DIR__ . '/../config/mailer.php';

class AppointmentController
{
    private AppointmentDao $dao;

    public function __construct()
    {
        $this->dao = new AppointmentDao();
    }

    public function index(): void
    {
        requireAuth();

        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 50;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;

        $filters = [
            'status'         => $_GET['status']         ?? null,
            'patient_id'     => $_GET['patient_id']     ?? null,
            'attention_area' => $_GET['attention_area'] ?? null,
            'date'           => $_GET['date']           ?? null,
        ];

        $datos = $this->dao->index($perPage, $offset, $filters);
        $total = $this->dao->countTotal($filters);

        jsonResponse('success', 'Citas obtenidas correctamente.', $datos, null, [
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function store(): void
    {
        $actor = requireAuth();
        $json  = getJsonInput();

        $areasStr  = implode(',', $this->dao->getValidAreas());
        $errors   = validar($json, [
            'patient_id'           => 'required|integer',
            'attention_area'       => "required|in:{$areasStr}",
            'reason'               => 'required|max:1000',
            'appointment_datetime' => 'required|after_now',
        ]);

        if (!empty($json['patient_id']) && !$this->dao->patientExists((int) $json['patient_id'])) {
            $errors['patient_id'][] = 'El paciente no existe.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $appointment = new Cita();
        $appointment->setAcceptedById($actor['id'] ?? null);
        $appointment->setPatientId((int) $json['patient_id']);
        $appointment->setAttentionArea($json['attention_area']);
        $appointment->setReason($json['reason']);
        $appointment->setAppointmentDatetime($json['appointment_datetime']);
        $appointment->setStatus('pending');

        $newId = $this->dao->store($appointment);

        $this->dao->logAction($actor['id'] ?? null, $newId, 'Create', null);

        $nueva = $this->dao->findById($newId);
        jsonResponse('success', 'Cita registrada correctamente.', $nueva, null, null, 201);
    }

    public function show(int $id): void
    {
        requireAuth();

        $appointment = $this->dao->findById($id);
        if (!$appointment) {
            jsonResponse('error', 'Cita no encontrada.', null, null, null, 404);
        }

        jsonResponse('success', 'Cita obtenida correctamente.', $this->dao->withRelations($appointment));
    }

    public function update(int $id): void
    {
        $actor = requireAuth();
        $appointment  = $this->dao->findById($id);

        if (!$appointment) {
            jsonResponse('error', 'Cita no encontrada.', null, null, null, 404);
        }

        $json     = getJsonInput();
        $areasStr = implode(',', $this->dao->getValidAreas());

        // Partial update: only validate fields present in the request body
        $baseRules = [
            'patient_id'           => 'required|integer',
            'attention_area'       => "required|in:{$areasStr}",
            'reason'               => 'required|max:1000',
            'appointment_datetime' => 'required|after_now',
        ];
        $errors = validar($json, array_intersect_key($baseRules, $json));

        if (!empty($json['patient_id']) && !$this->dao->patientExists((int) $json['patient_id'])) {
            $errors['patient_id'][] = 'El paciente no existe.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $allowedFields = ['patient_id', 'attention_area', 'reason', 'appointment_datetime'];
        $datos = array_merge(
            array_intersect_key($appointment, array_flip($allowedFields)),
            array_intersect_key($json,  array_flip($allowedFields))
        );

        $changes = [];
        foreach ($allowedFields as $campo) {
            if ((string) $datos[$campo] !== (string) $appointment[$campo]) {
                $changes[$campo] = ['from' => $appointment[$campo], 'to' => $datos[$campo]];
            }
        }

        $c = new Cita();
        $c->setPatientId((int) $datos['patient_id']);
        $c->setAttentionArea($datos['attention_area']);
        $c->setReason($datos['reason']);
        $c->setAppointmentDatetime($datos['appointment_datetime']);

        $this->dao->update($id, $c);
        $actualizada = $this->dao->findById($id);

        if ($changes) {
            $this->dao->logAction($actor['id'] ?? null, $id, 'Update', json_encode($changes));
        }

        jsonResponse('success', 'Cita actualizada correctamente.', $actualizada);
    }

    public function changeStatus(int $id): void
    {
        $actor  = requireAuth();
        $appointment   = $this->dao->findById($id);

        if (!$appointment) {
            jsonResponse('error', 'Cita no encontrada.', null, null, null, 404);
        }

        $json      = getJsonInput();
        $statusesStr = implode(',', $this->dao->getValidStatuses());
        $errors   = validar($json, [
            'status' => "required|in:{$statusesStr}",
        ]);

        // Accept 'cancellation_reason' or its short alias 'reason'
        $cancelReason        = $json['cancellation_reason'] ?? $json['reason'] ?? null;
        $requiresCancellation = in_array($json['status'] ?? '', ['canceled', 'rejected'], true);
        if ($requiresCancellation && empty($cancelReason)) {
            $errors['cancellation_reason'][] = 'El motivo de cancelación es obligatorio para este estado.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $oldStatus = $appointment['status'];
        $this->dao->changeStatus($id, $json['status'], $cancelReason);

        $this->dao->logAction(
            $actor['id'] ?? null,
            $id,
            'ChangeStatus',
            json_encode(['status' => ['from' => $oldStatus, 'to' => $json['status']]])
        );

        $actualizada = $this->dao->findById($id);

        if (!empty($actualizada['patient_email'])) {
            $this->sendAppointmentEmail($actualizada, $json['status']);
        }

        jsonResponse('success', 'Estado de la cita actualizado correctamente.', $actualizada);
    }

    public function confirm(int $id): void
    {
        $actor = requireAuth();
        $appointment  = $this->dao->findById($id);

        if (!$appointment) {
            jsonResponse('error', 'Cita no encontrada.', null, null, null, 404);
        }

        if (!in_array($appointment['status'], ['approved', 'pending'], true)) {
            jsonResponse('error', 'La cita no puede confirmarse en su estado actual.', null, null, null, 422);
        }

        $oldStatus = $appointment['status'];
        $this->dao->confirm($id);
        $this->dao->logAction(
            $actor['id'] ?? null, $id, 'Confirm',
            json_encode(['status' => ['from' => $oldStatus, 'to' => 'confirmed']])
        );

        jsonResponse('success', 'Asistencia confirmada correctamente.', $this->dao->findById($id));
    }

    public function confirmAttendance(int $id): void
    {
        // Public endpoint — confirms attendance without a session token
        $appointment = $this->dao->findById($id);

        if (!$appointment) {
            jsonResponse('error', 'Cita no encontrada.', null, null, null, 404);
        }

        if (!in_array($appointment['status'], ['approved', 'pending'], true)) {
            jsonResponse('error', 'La cita no puede confirmarse en su estado actual.', null, null, null, 422);
        }

        $this->dao->confirm($id);
        $this->dao->logAction(null, $id, 'Confirm', null);

        jsonResponse('success', 'Asistencia confirmada correctamente.', $this->dao->findById($id));
    }

    public function destroy(int $id): void
    {
        $actor = requireAuth();
        $appointment  = $this->dao->findById($id);

        if (!$appointment) {
            jsonResponse('error', 'Cita no encontrada.', null, null, null, 404);
        }

        $this->dao->destroy($id);
        $this->dao->logAction($actor['id'] ?? null, $id, 'Delete', null);

        http_response_code(204);
        exit;
    }

    public function calendar(): void
    {
        requireAuth();

        $fecha = $_GET['date'] ?? date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            jsonResponse('error', 'El parámetro date debe tener formato YYYY-MM-DD.', null, null, null, 422);
        }

        $datos = $this->dao->calendar($fecha);
        jsonResponse('success', "Citas del {$fecha} obtenidas correctamente.", $datos);
    }

    public function logs(): void
    {
        requireAuth();

        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 50;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;
        $filters = [
            'appointment_id'  => $_GET['appointment_id']  ?? null,
            'performed_by_id' => $_GET['performed_by_id'] ?? null,
            'action'          => $_GET['action']           ?? null,
            'date_from'       => $_GET['date_from']        ?? null,
            'date_to'         => $_GET['date_to']          ?? null,
        ];

        $datos = $this->dao->listLogs($perPage, $offset, $filters);
        $total = $this->dao->countLogs($filters);

        jsonResponse('success', 'Logs de citas obtenidos correctamente.', $datos, null, [
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function showLog(int $id): void
    {
        requireAuth();
        $log = $this->dao->findLogById($id);
        if (!$log) {
            jsonResponse('error', 'Log no encontrado.', null, null, null, 404);
        }
        jsonResponse('success', 'Log obtenido correctamente.', $log);
    }

    // -----------------------------------------------------------------------
    // Send email based on the new appointment status
    // -----------------------------------------------------------------------
    private function sendAppointmentEmail(array $appointment, string $newStatus): void
    {
        $asuntos = [
            'approved' => '[MediCode] Tu cita fue aprobada',
            'rejected' => '[MediCode] Tu cita fue rechazada',
            'canceled' => '[MediCode] Tu cita fue cancelada',
        ];

        if (!isset($asuntos[$newStatus])) return;

        try {
            (new Mailer())->send(
                $appointment['patient_email'],
                $asuntos[$newStatus],
                $this->buildAppointmentTemplate($appointment, $newStatus)
            );
        } catch (\RuntimeException) {
            // Fails silently; the status change has already been persisted
        }
    }

    private function buildAppointmentTemplate(array $appointment, string $estado): string
    {
        $patient = htmlspecialchars($appointment['patient_name'] ?? 'Paciente');
        $area     = htmlspecialchars($appointment['attention_area'] ?? '');
        $fecha    = date('d/m/Y H:i', strtotime($appointment['appointment_datetime'] ?? 'now'));
        $motivo   = htmlspecialchars($appointment['cancellation_reason'] ?? '');

        $mensajes = [
            'approved' => 'Tu cita fue aprobada. Estos son los detalles:',
            'rejected' => 'Lamentamos informarte que tu cita fue rechazada. Estos son los detalles:',
            'canceled' => 'Tu cita fue cancelada. Estos son los detalles:',
        ];

        $mensaje = $mensajes[$estado];

        $bloqueMotivo = '';
        if ($motivo && in_array($estado, ['rejected', 'canceled'], true)) {
            $bloqueMotivo = '<div style="background:#fef2f2;border-left:4px solid #dc2626;padding:12px 16px;border-radius:4px;font-size:14px;color:#991b1b;margin-top:16px;">'
                          . '<strong>Motivo:</strong> ' . $motivo
                          . '</div>';
        }

        $notaFinal = $estado === 'approved'
            ? '<p style="font-size:14px;color:#6b7280;">Recibirás un recordatorio el día de la cita para confirmar tu asistencia.</p>'
            : '<p style="font-size:14px;color:#6b7280;">Si tienes dudas, puedes solicitar una nueva cita o comunicarte con el centro de salud.</p>';

        return '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
             . '<body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
             . '<div style="max-width:600px;margin:40px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">'
             . '<div style="background:#1a56db;padding:32px;text-align:center;"><h1 style="color:#fff;margin:0;font-size:24px;">MediCode UCR</h1></div>'
             . '<div style="padding:32px;color:#333;">'
             . '<p>Hola, <strong>' . $patient . '</strong>.</p>'
             . '<p>' . $mensaje . '</p>'
             . '<div style="background:#f3f4f6;border-radius:6px;padding:20px 24px;margin:24px 0;">'
             . '<p style="margin:0 0 12px 0;"><strong>Área de atención:</strong> ' . $area . '</p>'
             . '<p style="margin:0;"><strong>Fecha y hora:</strong> ' . $fecha . '</p>'
             . '</div>'
             . $bloqueMotivo
             . $notaFinal
             . '</div>'
             . '<div style="padding:24px 32px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;">'
             . 'MediCode — Sistema de Citas Médicas UCR &bull; Correo automático, no responder.'
             . '</div></div></body></html>';
    }
}
