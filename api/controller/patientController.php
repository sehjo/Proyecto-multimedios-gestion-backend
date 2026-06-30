<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/patientDao.php';
require_once __DIR__ . '/../models/patient.php';

class PatientController
{
    private $dao;

    public function __construct()
    {
        $this->dao = new PatientDao();
    }

    public function index(): void
    {
        requirePermission('patients.read');

        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 50;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;

        $filters = [
            'full_name'  => $_GET['full_name']  ?? null,
            'identifier' => $_GET['identifier'] ?? null,
            'user_type'  => $_GET['user_type']  ?? null,
        ];

        $datos = $this->dao->index($perPage, $offset, $filters);
        $total = $this->dao->countTotal($filters);

        jsonResponse('success', 'Pacientes obtenidos correctamente.', $datos, null, [
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
        $actor = requirePermission('patients.create');
        $json = getJsonInput();

        $errors = validar($json, [
            'full_name'  => 'required|max:255',
            'identifier' => 'required|max:50|regex:/^\d{1}-\d{4}-\d{4}$/',
            'email'      => 'required|email|max:254',
            'birth_date' => 'required|date|before_today',
            'user_type'  => 'required|max:100',
            'phone'      => 'required|max:20|regex:/^(\+\d{1,3}\s)?\d{4}[\s\-]\d{4}$/',
        ]);

        if ($this->dao->identifierExists($json['identifier'] ?? '')) {
            $errors['identifier'][] = 'El identificador ya está registrado.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $patient = new Patient();
        $patient->setFullName($json['full_name']);
        $patient->setIdentifier($json['identifier']);
        $patient->setEmail($json['email']);
        $patient->setBirthDate($json['birth_date']);
        $patient->setUserType($json['user_type']);
        $patient->setPhone($json['phone']);

        $newId = $this->dao->store($patient);
        $created   = $this->dao->findById($newId);

        $this->dao->logAction($actor['id'] ?? null, $newId, 'Create', null);

        jsonResponse('success', 'Paciente registrado correctamente.', $created, null, null, 201);
    }

    public function show(int $id): void
    {
        requirePermission('patients.read');

        $patient = $this->dao->findById($id);
        if (!$patient) {
            jsonResponse('error', 'Paciente no encontrado.', null, null, null, 404);
        }

        jsonResponse('success', 'Paciente obtenido correctamente.', $patient);
    }

    public function update(int $id): void
    {
        $actor    = requirePermission('patients.update');
        $patient = $this->dao->findById($id);

        if (!$patient) {
            jsonResponse('error', 'Paciente no encontrado.', null, null, null, 404);
        }

        $json = getJsonInput();

        // Partial update: only validate fields present in the request body
        $baseRules = [
            'full_name'  => 'required|max:255',
            'identifier' => 'required|max:50|regex:/^\d{1}-\d{4}-\d{4}$/',
            'email'      => 'required|email|max:254',
            'birth_date' => 'required|date|before_today',
            'user_type'  => 'required|max:100',
            'phone'      => 'required|max:20|regex:/^(\+\d{1,3}\s)?\d{4}[\s\-]\d{4}$/',
        ];
        $errors = validar($json, array_intersect_key($baseRules, $json));

        $identificadorNuevo = $json['identifier'] ?? $patient['identifier'];
        if ($identificadorNuevo !== $patient['identifier'] && $this->dao->identifierExists($identificadorNuevo, $id)) {
            $errors['identifier'][] = 'El identificador ya está registrado.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        // Merge body fields with existing values for any fields not provided
        $allowedFields = ['full_name', 'identifier', 'email', 'birth_date', 'user_type', 'phone'];
        $datos = array_merge(
            array_intersect_key($patient, array_flip($allowedFields)),
            array_intersect_key($json,     array_flip($allowedFields))
        );

        $changes = [];
        foreach ($allowedFields as $c) {
            if ((string) $datos[$c] !== (string) $patient[$c]) {
                $changes[$c] = ['from' => $patient[$c], 'to' => $datos[$c]];
            }
        }

        $p = new Patient();
        $p->setFullName($datos['full_name']);
        $p->setIdentifier($datos['identifier']);
        $p->setEmail($datos['email']);
        $p->setBirthDate($datos['birth_date']);
        $p->setUserType($datos['user_type']);
        $p->setPhone($datos['phone']);

        $this->dao->update($id, $p);
        $updated = $this->dao->findById($id);

        if ($changes) {
            $this->dao->logAction($actor['id'] ?? null, $id, 'Update', json_encode($changes));
        }

        jsonResponse('success', 'Paciente actualizado correctamente.', $updated);
    }

    public function destroy(int $id): void
    {
        $actor    = requirePermission('patients.delete');
        $patient = $this->dao->findById($id);

        if (!$patient) {
            jsonResponse('error', 'Paciente no encontrado.', null, null, null, 404);
        }

        try {
            $this->dao->destroy($id);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                jsonResponse('error', 'No se puede eliminar el paciente porque tiene citas asociadas.', null, null, null, 409);
            }
            throw $e;
        }
        $this->dao->logAction($actor['id'] ?? null, $id, 'Delete', null);

        http_response_code(204);
        exit;
    }

    public function logs(): void
    {
        requirePermission('logs.patients');

        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 50;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;

        $datos = $this->dao->listLogs($perPage, $offset);
        $total = $this->dao->countLogs();

        jsonResponse('success', 'Logs de pacientes obtenidos correctamente.', $datos, null, [
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }
}
