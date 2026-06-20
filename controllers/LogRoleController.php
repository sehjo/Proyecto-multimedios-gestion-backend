<?php

class LogRoleController
{
    /**
     * Paginated role audit logs (read-only). Requires logs_roles.read.
     */
    public function index(Request $request): void
    {
        if (!Guard::permission($request, 'logs_roles.read')) {
            return;
        }

        $params = Paginator::params($request, 15);
        $rows = LogRoleRepository::paginate($params['offset'], $params['perPage']);
        $total = LogRoleRepository::count();

        Response::json([
            'data' => LogResource::collection($rows),
            'meta' => Paginator::meta($total, $params['page'], $params['perPage']),
        ]);
    }
}
