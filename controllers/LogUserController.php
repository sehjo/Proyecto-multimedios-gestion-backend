<?php

class LogUserController
{
    /**
     * Paginated user audit logs (read-only). Requires logs_users.read.
     */
    public function index(Request $request): void
    {
        if (!Guard::permission($request, 'logs_users.read')) {
            return;
        }

        $params = Paginator::params($request, 15);
        $rows = LogUserRepository::paginate($params['offset'], $params['perPage']);
        $total = LogUserRepository::count();

        Response::json([
            'data' => LogResource::collection($rows),
            'meta' => Paginator::meta($total, $params['page'], $params['perPage']),
        ]);
    }
}
