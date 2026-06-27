<?php

require_once __DIR__ . '/env.php';

class Connection
{
    public function connect(): PDO
    {
        $host = Env::get('DB_HOST',     '127.0.0.1');
        $port = Env::get('DB_PORT',     '3306');
        $db   = Env::get('DB_DATABASE', 'db_ccss');
        $user = Env::get('DB_USERNAME', 'root');
        $pass = Env::get('DB_PASSWORD', '');

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
                $user,
                $pass
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error de conexión a la base de datos.',
                'data'    => null,
                'errors'  => null,
                'meta'    => null,
            ]);
            exit;
        }
    }
}
