<?php

class Conexion
{
    private string $host;
    private string $port;
    private string $database;
    private string $user;
    private string $password;
    private string $charset;

    public function __construct()
    {
        $this->host = config('database.host');
        $this->port = config('database.port');
        $this->database = config('database.database');
        $this->user = config('database.username');
        $this->password = config('database.password');
        $this->charset = config('database.charset');
    }

    public function Conectar(): PDO
    {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset={$this->charset}";

            $conexion = new PDO($dsn, $this->user, $this->password);
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $conexion;
        } catch (PDOException $exc) {
            die($exc->getMessage());
        }
    }
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = (new Conexion())->Conectar();
    }

    return $pdo;
}
