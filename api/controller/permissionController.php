<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/permissionDao.php';

class PermissionController
{
    private $dao;

    public function __construct()
    {
        $this->dao = new PermissionDao();
    }

    public function index(): void
    {
        requireAuth();
        jsonResponse('success', 'Permisos obtenidos correctamente.', $this->dao->list());
    }
}
