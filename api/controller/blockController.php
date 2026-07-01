<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/blockDao.php';

class BlockController
{
    private $dao;

    public function __construct()
    {
        $this->dao = new BlockDao();
    }

    public function index(): void
    {
        requireAuth();
        $filters = [
            'date'     => $_GET['date']     ?? null,
            'full_day' => $_GET['full_day'] ?? null,
            'from'     => $_GET['from']     ?? null,
            'to'       => $_GET['to']       ?? null,
        ];
        jsonResponse('success', 'Bloqueos institucionales obtenidos correctamente.', $this->dao->index($filters));
    }

    public function show(int $id): void
    {
        requireAuth();
        $block = $this->dao->findById($id);
        if (!$block) {
            jsonResponse('error', 'Bloqueo no encontrado.', null, null, null, 404);
        }
        jsonResponse('success', 'Bloqueo obtenido correctamente.', $block);
    }

    public function store(): void
    {
        $actor   = requirePermission('blocks.create');
        $json    = getJsonInput();
        // Note: `full_day` may be boolean false which PHP treats as empty in some validators.
        // Validate `date` normally and ensure `full_day` key is present explicitly.
        $errors = validar($json, [
            'date'     => 'required|date',
        ]);
        if (!array_key_exists('full_day', $json)) {
            $errors['full_day'][] = 'El campo full_day es obligatorio.';
        }

        if (isset($json['date']) && strtotime($json['date']) < strtotime(date('Y-m-d'))) {
            $errors['date'][] = 'La fecha del bloqueo no puede ser en el pasado.';
        }

        if (empty($errors) && !empty($json['date']) && $this->dao->dateExists($json['date'])) {
            $errors['date'][] = 'Ya existe un bloqueo institucional para esta fecha.';
        }

        $fullDay = isset($json['full_day']) ? filter_var($json['full_day'], FILTER_VALIDATE_BOOLEAN) : true;
        if ($fullDay) {
            $json['start_time'] = null;
            $json['end_time']   = null;
        } else {
            if (empty($json['start_time'])) {
                $errors['start_time'][] = 'El campo start_time es obligatorio cuando no es día completo.';
            }
            if (empty($json['end_time'])) {
                $errors['end_time'][] = 'El campo end_time es obligatorio cuando no es día completo.';
            }
            if (!empty($json['start_time']) && !empty($json['end_time']) && $json['end_time'] <= $json['start_time']) {
                $errors['end_time'][] = 'La hora de fin debe ser posterior a la hora de inicio.';
            }
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $newId = $this->dao->store($json, (int) $actor['id']);
        $created   = $this->dao->findById($newId);
        $this->dao->logAction($actor['id'] ?? null, $newId, 'Create', null);

        jsonResponse('success', 'Bloqueo institucional creado correctamente.', $created, null, null, 201);
    }

    public function update(int $id): void
    {
        $actor   = requirePermission('blocks.update');
        $block = $this->dao->findById($id);

        if (!$block) {
            jsonResponse('error', 'Bloqueo no encontrado.', null, null, null, 404);
        }

        $json    = getJsonInput();
        $errors = [];

        if (isset($json['date'])) {
            $d = DateTime::createFromFormat('Y-m-d', $json['date']);
            if (!$d || $d->format('Y-m-d') !== $json['date']) {
                $errors['date'][] = 'La fecha debe tener formato YYYY-MM-DD.';
            } elseif (strtotime($json['date']) < strtotime(date('Y-m-d'))) {
                $errors['date'][] = 'La fecha del bloqueo no puede ser en el pasado.';
            } elseif ($json['date'] !== $block['date'] && $this->dao->dateExists($json['date'], $id)) {
                $errors['date'][] = 'Ya existe un bloqueo institucional para esta fecha.';
            }
        }

        $fullDay = isset($json['full_day']) ? filter_var($json['full_day'], FILTER_VALIDATE_BOOLEAN) : (bool) $block['full_day'];
        if ($fullDay) {
            $json['start_time'] = null;
            $json['end_time']   = null;
        } else {
            $startTime = isset($json['start_time']) ? $json['start_time'] : $block['start_time'];
            $endTime   = isset($json['end_time'])   ? $json['end_time']   : $block['end_time'];
            if (empty($startTime)) {
                $errors['start_time'][] = 'El campo start_time es obligatorio cuando no es día completo.';
            }
            if (empty($endTime)) {
                $errors['end_time'][] = 'El campo end_time es obligatorio cuando no es día completo.';
            }
            if (!empty($startTime) && !empty($endTime) && $endTime <= $startTime) {
                $errors['end_time'][] = 'La hora de fin debe ser posterior a la hora de inicio.';
            }
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $datos = [
            'date'       => $json['date']       ?? $block['date'],
            'reason'     => $json['reason']     ?? $block['reason'],
            'full_day'   => $fullDay ? 1 : 0,
            'start_time' => $json['start_time'] ?? ($fullDay ? null : $block['start_time']),
            'end_time'   => $json['end_time']   ?? ($fullDay ? null : $block['end_time']),
        ];

        $this->dao->update($id, $datos);
        $this->dao->logAction($actor['id'] ?? null, $id, 'Update', null);

        jsonResponse('success', 'Bloqueo actualizado correctamente.', $this->dao->findById($id));
    }

    public function destroy(int $id): void
    {
        $actor   = requirePermission('blocks.delete');
        $block = $this->dao->findById($id);

        if (!$block) {
            jsonResponse('error', 'Bloqueo no encontrado.', null, null, null, 404);
        }

        $this->dao->destroy($id);
        $this->dao->logAction($actor['id'] ?? null, $id, 'Delete', null);

        http_response_code(204);
        exit;
    }
}
