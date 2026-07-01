<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/roleDao.php';

class RoleController
{
    private $dao;

    public function __construct()
    {
        $this->dao = new RoleDao();
    }

    public function index(): void
    {
        requirePermission('roles.read');
        jsonResponse('success', 'Roles obtenidos correctamente.', $this->dao->index());
    }

    public function store(): void
    {
        $actor   = requirePermission('roles.create');
        $json    = getJsonInput();
        $errors = validar($json, ['name' => 'required|max:50']);

        if (empty($json['permissions']) || !is_array($json['permissions'])) {
            $errors['permissions'][] = 'El campo permissions es obligatorio y debe ser un arreglo con al menos un permiso.';
        }

        if ($this->dao->nameExists($json['name'] ?? '')) {
            $errors['name'][] = 'Ya existe un rol con ese nombre.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $newId = $this->dao->store($json['name'], $json['permissions']);
        $created   = $this->dao->findById($newId);

        $this->dao->logAction($actor['id'] ?? null, $newId, 'Create', null);

        jsonResponse('success', 'Rol creado correctamente.', $created, null, null, 201);
    }

    public function logs(): void
    {
        requirePermission('roles.read');

        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 50;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;

        $datos = $this->dao->listLogs($perPage, $offset);
        $total = $this->dao->countLogs();

        jsonResponse('success', 'Logs de roles obtenidos correctamente.', $datos, null, [
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function update(int $id): void
    {
        $actor = requirePermission('roles.update');

        // Authorization before DB lookup: 403 if actor owns this role
        $actorRoles = $this->dao->userRoleIds((int) $actor['id']);
        if (in_array($id, $actorRoles, true)) {
            jsonResponse('error', 'No puedes modificar un rol que tienes asignado.', null, null, null, 403);
        }

        $role = $this->dao->findById($id);

        if (!$role) {
            jsonResponse('error', 'Rol no encontrado.', null, null, null, 404);
        }

        $json    = getJsonInput();
        $errors = [];

        if (isset($json['name'])) {
            $e = validar($json, ['name' => 'required|max:50']);
            if (!empty($e)) $errors = array_merge($errors, $e);

            if ($json['name'] !== $role['name'] && $this->dao->nameExists($json['name'], $id)) {
                $errors['name'][] = 'Ya existe un rol con ese nombre.';
            }
        }

        if (isset($json['permissions']) && (!is_array($json['permissions']) || empty($json['permissions']))) {
            $errors['permissions'][] = 'El arreglo de permisos debe tener al menos un elemento.';
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $oldPermissions = array_column($role['permissions'], 'name');
        $this->dao->update($id, $json, $json['permissions'] ?? null);
        $updated = $this->dao->findById($id);

        $changes = [];
        if (isset($json['name']) && $json['name'] !== $role['name']) {
            $changes['name'] = ['from' => $role['name'], 'to' => $json['name']];
        }
        if (isset($json['permissions'])) {
            $newPermissions = array_column($updated['permissions'], 'name');
            $changes['permissions'] = ['from' => $oldPermissions, 'to' => $newPermissions];
        }

        $this->dao->logAction(
            $actor['id'] ?? null,
            $id,
            'Update',
            $changes ? json_encode($changes) : null
        );

        jsonResponse('success', 'Rol actualizado correctamente.', $updated);
    }

    public function destroy(int $id): void
    {
        $actor = requirePermission('roles.delete');

        // Authorization before DB lookup: 403 if actor owns this role
        $actorRoles = $this->dao->userRoleIds((int) $actor['id']);
        if (in_array($id, $actorRoles, true)) {
            jsonResponse('error', 'No puedes eliminar un rol que tienes asignado.', null, null, null, 403);
        }

        $role = $this->dao->findById($id);

        if (!$role) {
            jsonResponse('error', 'Rol no encontrado.', null, null, null, 404);
        }

        $this->dao->destroy($id);
        $this->dao->logAction($actor['id'] ?? null, $id, 'Delete', null);

        http_response_code(204);
        exit;
    }
}
