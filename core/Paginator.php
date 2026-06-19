<?php

class Paginator
{
    public static function params(Request $request, int $defaultPerPage = 20): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, (int) $request->query('per_page', $defaultPerPage));

        return [
            'page' => $page,
            'perPage' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    public static function meta(int $total, int $page, int $perPage): array
    {
        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => $total === 0 ? null : ($page - 1) * $perPage + 1,
            'to' => $total === 0 ? null : min($total, $page * $perPage),
        ];
    }
}
