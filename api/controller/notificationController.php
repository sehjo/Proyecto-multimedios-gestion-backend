<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/notificationDao.php';

class NotificationController
{
    private NotificationDao $dao;

    public function __construct()
    {
        $this->dao = new NotificationDao();
    }

    public function index(): void
    {
        $actor   = requireAuth();
        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 20;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;

        $datos = $this->dao->index((int) $actor['id'], $perPage, $offset);
        $total = $this->dao->countTotal((int) $actor['id']);

        jsonResponse('success', 'Notificaciones obtenidas correctamente.', $datos, null, [
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }

    public function unread(): void
    {
        $actor   = requireAuth();
        $perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 20;
        $page    = isset($_GET['page'])     ? max(1, (int) $_GET['page'])     : 1;
        $offset  = ($page - 1) * $perPage;

        $datos = $this->dao->listUnread((int) $actor['id'], $perPage, $offset);
        jsonResponse('success', 'Notificaciones no leídas obtenidas correctamente.', $datos);
    }

    public function countUnread(): void
    {
        $actor = requireAuth();
        jsonResponse('success', 'Conteo de notificaciones no leídas.', [
            'count' => $this->dao->countUnread((int) $actor['id']),
        ]);
    }

    public function markAsRead(int $id): void
    {
        $actor = requireAuth();
        $notif = $this->dao->findById($id, (int) $actor['id']);

        if (!$notif) {
            jsonResponse('error', 'Notificación no encontrada.', null, null, null, 404);
        }

        // Idempotent: mark as read regardless of current state
        $this->dao->markAsRead($id, (int) $actor['id']);
        $actualizada = $this->dao->findById($id, (int) $actor['id']);
        jsonResponse('success', 'Notificación marcada como leída.', $actualizada);
    }

    public function markAsUnread(int $id): void
    {
        $actor = requireAuth();
        $notif = $this->dao->findById($id, (int) $actor['id']);

        if (!$notif) {
            jsonResponse('error', 'Notificación no encontrada.', null, null, null, 404);
        }

        $this->dao->markAsUnread($id, (int) $actor['id']);
        $actualizada = $this->dao->findById($id, (int) $actor['id']);
        jsonResponse('success', 'Notificación marcada como no leída.', $actualizada);
    }

    public function markAllAsRead(): void
    {
        $actor   = requireAuth();
        $updated = $this->dao->markAllAsRead((int) $actor['id']);
        jsonResponse('success', 'Todas las notificaciones fueron marcadas como leídas.', [
            'updated' => $updated,
        ]);
    }

    public function destroy(int $id): void
    {
        $actor = requireAuth();
        $this->dao->destroy($id, (int) $actor['id']);

        http_response_code(204);
        exit;
    }
}
