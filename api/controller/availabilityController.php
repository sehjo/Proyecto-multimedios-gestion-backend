<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/availabilityDao.php';

class AvailabilityController
{
    private $dao;

    public function __construct()
    {
        $this->dao = new AvailabilityDao();
    }

    public function index(): void
    {
        requireAuth();
        $enabled = isset($_GET['enabled'])
            ? filter_var($_GET['enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        jsonResponse('success', 'Disponibilidad institucional obtenida correctamente.', $this->dao->list($enabled));
    }

    public function show(int $id): void
    {
        requireAuth();
        $slot = $this->dao->findById($id);
        if (!$slot) {
            jsonResponse('error', 'Slot de disponibilidad no encontrado.', null, null, null, 404);
        }
        jsonResponse('success', 'Slot obtenido correctamente.', $slot);
    }

    public function store(): void
    {
        $actor = requireAuth();
        $json = getJsonInput();

        if (isset($json['day_of_week'])) {
            $json['day_of_week'] = strtolower(trim($json['day_of_week']));
        }

        $daysStr = implode(',', $this->dao->getValidDays());
        $errors = validar($json, [
            'day_of_week' => "required|in:{$daysStr}",
        ]);

        $enabled = isset($json['enabled']) ? filter_var($json['enabled'], FILTER_VALIDATE_BOOLEAN) : true;
        if ($enabled) {
            if (empty($json['start_time'])) {
                $errors['start_time'][] = 'El campo start_time es obligatorio cuando el slot está habilitado.';
            }
            if (empty($json['end_time'])) {
                $errors['end_time'][] = 'El campo end_time es obligatorio cuando el slot está habilitado.';
            }
            if (!empty($json['start_time']) && !empty($json['end_time']) && $json['end_time'] <= $json['start_time']) {
                $errors['end_time'][] = 'La hora de fin debe ser posterior a la hora de inicio.';
            }
        } else {
            $json['start_time'] = null;
            $json['end_time']   = null;
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        if ($enabled && !empty($json['start_time']) && !empty($json['end_time'])) {
            if ($this->dao->overlapExists($json['day_of_week'], $json['start_time'], $json['end_time'])) {
                jsonResponse('error', 'Error de validación.', null, [
                    'start_time' => ['El horario se solapa con un slot de disponibilidad existente.'],
                ], null, 422);
            }
        }

        $newId = $this->dao->save($json);
        $created   = $this->dao->findById($newId);
        $this->dao->logAction($actor['id'] ?? null, 'InstitutionAvailability', $newId, 'Create', null);

        jsonResponse('success', 'Slot de disponibilidad creado correctamente.', $created, null, null, 201);
    }

    public function update(int $id): void
    {
        $actor = requireAuth();
        $slot  = $this->dao->findById($id);

        if (!$slot) {
            jsonResponse('error', 'Slot de disponibilidad no encontrado.', null, null, null, 404);
        }

        $json = getJsonInput();
        $daysStr = implode(',', $this->dao->getValidDays());
        $errors = validar($json, ['day_of_week' => "required|in:{$daysStr}"]);

        $enabled = isset($json['enabled']) ? (bool) $json['enabled'] : (bool) $slot['enabled'];
        if ($enabled) {
            if (empty($json['start_time'] ?? $slot['start_time'])) {
                $errors['start_time'][] = 'El campo start_time es obligatorio cuando el slot está habilitado.';
            }
            if (empty($json['end_time'] ?? $slot['end_time'])) {
                $errors['end_time'][] = 'El campo end_time es obligatorio cuando el slot está habilitado.';
            }
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        // Merge defaults from existing slot for optional fields
        $startTime = $json['start_time'] ?? $slot['start_time'];
        $endTime   = $json['end_time']   ?? $slot['end_time'];
        if ($enabled && $startTime && $endTime) {
            if ($this->dao->overlapExists($json['day_of_week'] ?? $slot['day_of_week'], $startTime, $endTime, $id)) {
                jsonResponse('error', 'Error de validación.', null, [
                    'start_time' => ['El horario se solapa con un slot de disponibilidad existente.'],
                ], null, 422);
            }
        }

        $datos = [
            'day_of_week' => $json['day_of_week'] ?? $slot['day_of_week'],
            'start_time'  => $json['start_time']  ?? $slot['start_time'],
            'end_time'    => $json['end_time']     ?? $slot['end_time'],
            'enabled'     => $json['enabled']      ?? $slot['enabled'],
        ];

        $this->dao->update($id, $datos);
        $this->dao->logAction($actor['id'] ?? null, 'InstitutionAvailability', $id, 'Update', null);

        jsonResponse('success', 'Slot de disponibilidad actualizado correctamente.', $this->dao->findById($id));
    }

    public function destroy(int $id): void
    {
        $actor = requireAuth();
        $slot  = $this->dao->findById($id);

        if (!$slot) {
            jsonResponse('error', 'Slot de disponibilidad no encontrado.', null, null, null, 404);
        }

        $this->dao->destroy($id);
        $this->dao->logAction($actor['id'] ?? null, 'InstitutionAvailability', $id, 'Delete', null);

        http_response_code(204);
        exit;
    }
}
