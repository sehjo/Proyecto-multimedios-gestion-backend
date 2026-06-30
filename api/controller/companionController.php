<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/companionDao.php';
require_once __DIR__ . '/../models/companion.php';

class CompanionController
{
    private $dao;

    public function __construct()
    {
        $this->dao = new CompanionDao();
    }

    public function index(): void
    {
        requirePermission('companions.read');

        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 50;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;

        $filters = [
            'full_name'  => $_GET['full_name']  ?? null,
            'identifier' => $_GET['identifier'] ?? null,
        ];

        $datos = $this->dao->index($perPage, $offset, $filters);
        $total = $this->dao->countTotal($filters);

        jsonResponse('success', 'Acompañantes obtenidos correctamente.', $datos, null, [
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function show(int $id): void
    {
        requirePermission('companions.read');

        $companion = $this->dao->findById($id);
        if (!$companion) {
            jsonResponse('error', 'Acompañante no encontrado.', null, null, null, 404);
        }

        jsonResponse('success', 'Acompañante obtenido correctamente.', $companion);
    }

    public function store(): void
    {
        requirePermission('companions.create');
        $json = getJsonInput();

        $errors = validar($json, [
            'full_name'  => 'required|max:255',
            'identifier' => 'required|max:50|regex:/^\d{1}-\d{4}-\d{4}$/',
            'phone'      => 'required|max:20|regex:/^(\+\d{1,3}\s)?\d{4}[\s\-]\d{4}$/',
        ]);

        if ($this->dao->identifierExists($json['identifier'] ?? '')) {
            $errors['identifier'][] = 'El identificador ya está registrado.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $a = new Companion();
        $a->setFullName($json['full_name']);
        $a->setIdentifier($json['identifier']);
        $a->setPhone($json['phone']);

        $newId = $this->dao->store($a);
        $created   = $this->dao->findById($newId);

        jsonResponse('success', 'Acompañante registrado correctamente.', $created, null, null, 201);
    }

    public function update(int $id): void
    {
        requirePermission('companions.update');

        $companion = $this->dao->findById($id);
        if (!$companion) {
            jsonResponse('error', 'Acompañante no encontrado.', null, null, null, 404);
        }

        $json = getJsonInput();
        $errors = validar($json, [
            'full_name'  => 'required|max:255',
            'identifier' => 'required|max:50|regex:/^\d{1}-\d{4}-\d{4}$/',
            'phone'      => 'required|max:20|regex:/^(\+\d{1,3}\s)?\d{4}[\s\-]\d{4}$/',
        ]);

        if (($json['identifier'] ?? '') !== $companion['identifier'] && $this->dao->identifierExists($json['identifier'] ?? '', $id)) {
            $errors['identifier'][] = 'El identificador ya está registrado.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $a = new Companion();
        $a->setFullName($json['full_name']);
        $a->setIdentifier($json['identifier']);
        $a->setPhone($json['phone']);

        $this->dao->update($id, $a);
        $updated = $this->dao->findById($id);

        jsonResponse('success', 'Acompañante actualizado correctamente.', $updated);
    }

    public function destroy(int $id): void
    {
        requirePermission('companions.delete');

        $companion = $this->dao->findById($id);
        if (!$companion) {
            jsonResponse('error', 'Acompañante no encontrado.', null, null, null, 404);
        }

        $this->dao->destroy($id);

        http_response_code(204);
        exit;
    }
}
