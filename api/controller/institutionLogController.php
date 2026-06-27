<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/institutionLogDao.php';

class InstitutionLogController
{
    private $dao;

    public function __construct()
    {
        $this->dao = new InstitutionLogDao();
    }

    public function index(): void
    {
        requireAuth();

        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 50;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;

        $filters = [
            'action'       => $_GET['action']       ?? null,
            'target_type'  => $_GET['target_type']  ?? null,
            'performed_by' => $_GET['performed_by'] ?? null,
            'from'         => $_GET['from']         ?? null,
            'to'           => $_GET['to']           ?? null,
        ];

        $datos = $this->dao->index($perPage, $offset, $filters);
        $total = $this->dao->countTotal($filters);

        jsonResponse('success', 'Logs institucionales obtenidos correctamente.', $datos, null, [
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
        requireAuth();
        $log = $this->dao->findById($id);
        if (!$log) {
            jsonResponse('error', 'Log no encontrado.', null, null, null, 404);
        }
        jsonResponse('success', 'Log institucional obtenido correctamente.', $log);
    }
}
