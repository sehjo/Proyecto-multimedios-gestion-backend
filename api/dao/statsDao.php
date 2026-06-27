<?php

require_once __DIR__ . '/../config/connection.php';

class StatsDao
{
    private \PDO $connection;

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    // ---- Pacientes ----

    public function totalPatients(?string $from = null, ?string $to = null): array
    {
        $where  = $this->patientDateRange($from, $to);
        $stmt   = $this->connection->prepare("SELECT COUNT(*) AS total FROM patients" . $where['sql']);
        $stmt->execute($where['params']);
        return $stmt->fetch();
    }

    public function underagePatients(?string $from = null, ?string $to = null): array
    {
        $base   = "FROM patients WHERE TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18";
        $rango  = $this->patientDateRange($from, $to, true);
        $stmtM  = $this->connection->prepare("SELECT COUNT(*) AS minors " . $base . $rango['sql']);
        $stmtM->execute($rango['params']);
        $stmtA  = $this->connection->prepare(
            "SELECT COUNT(*) AS adults FROM patients WHERE TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= 18" . $rango['sql']
        );
        $stmtA->execute($rango['params']);
        return [
            'minors' => (int) $stmtM->fetch()['minors'],
            'adults' => (int) $stmtA->fetch()['adults'],
        ];
    }

    public function patientsByType(): array
    {
        $stmt = $this->connection->query(
            "SELECT user_type, COUNT(*) AS total FROM patients GROUP BY user_type ORDER BY total DESC"
        );
        return $stmt->fetchAll();
    }

    public function patientsByPeriod(string $dateFrom, string $dateTo, string $period = 'day'): array
    {
        $formato = $this->formatPeriod($period);
        $stmt    = $this->connection->prepare(
            "SELECT {$formato} AS periodo, COUNT(*) AS total
             FROM patients
             WHERE DATE(created_at) BETWEEN :date_from AND :date_to
             GROUP BY periodo ORDER BY MIN(created_at) ASC"
        );
        $stmt->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
        return $stmt->fetchAll();
    }

    // ---- Citas ----

    public function appointmentsByStatus(?string $from = null, ?string $to = null): array
    {
        $where = $this->appointmentDateRange($from, $to);
        $stmt  = $this->connection->prepare(
            "SELECT status, COUNT(*) AS `count` FROM appointments" . $where['sql'] . " GROUP BY status ORDER BY `count` DESC"
        );
        $stmt->execute($where['params']);
        $rows = $stmt->fetchAll();

        $grandTotal = array_sum(array_column($rows, 'count'));
        foreach ($rows as &$f) {
            $f['percentage'] = $grandTotal > 0 ? round($f['count'] / $grandTotal * 100, 2) : 0;
        }
        return $rows;
    }

    public function appointmentsByArea(?string $from = null, ?string $to = null): array
    {
        $where = $this->appointmentDateRange($from, $to);
        $stmt  = $this->connection->prepare(
            "SELECT attention_area, COUNT(*) AS total FROM appointments" . $where['sql'] . " GROUP BY attention_area ORDER BY total DESC"
        );
        $stmt->execute($where['params']);
        return $stmt->fetchAll();
    }

    public function appointmentsByPeriod(string $dateFrom, string $dateTo, string $period = 'day'): array
    {
        $formato = $this->formatPeriodAppointments($period);
        $stmt    = $this->connection->prepare(
            "SELECT {$formato} AS periodo, COUNT(*) AS total
             FROM appointments
             WHERE DATE(appointment_datetime) BETWEEN :date_from AND :date_to
             GROUP BY periodo ORDER BY MIN(appointment_datetime) ASC"
        );
        $stmt->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
        return $stmt->fetchAll();
    }

    public function cancellations(?string $from = null, ?string $to = null): array
    {
        $where = $this->appointmentDateRange($from, $to);
        $stmtT = $this->connection->prepare("SELECT COUNT(*) AS total FROM appointments" . $where['sql']);
        $stmtT->execute($where['params']);
        $total = (int) $stmtT->fetch()['total'];

        $condC  = $where['sql'] ? $where['sql'] . " AND status IN ('canceled','rejected')" : " WHERE status IN ('canceled','rejected')";
        $stmtC  = $this->connection->prepare("SELECT COUNT(*) AS total FROM appointments" . $condC);
        $stmtC->execute($where['params']);
        $cancelledCount = (int) $stmtC->fetch()['total'];

        return [
            'total'              => $total,
            'cancellations'      => $cancelledCount,
            'cancellation_rate'  => $total > 0 ? round($cancelledCount / $total * 100, 2) : 0,
        ];
    }

    // ---- Usuarios ----

    public function usersByStatus(): array
    {
        $stmt = $this->connection->query(
            "SELECT status, COUNT(*) AS total FROM users WHERE deleted_at IS NULL GROUP BY status"
        );
        return $stmt->fetchAll();
    }

    public function usersByRole(): array
    {
        $stmt = $this->connection->query(
            "SELECT r.name AS role, COUNT(uhr.user_id) AS total
             FROM roles r
             LEFT JOIN user_has_roles uhr ON uhr.role_id = r.id
             GROUP BY r.id, r.name ORDER BY total DESC"
        );
        return $stmt->fetchAll();
    }

    // ---- Helpers ----

    private function patientDateRange(?string $from, ?string $to, bool $extraAnd = false): array
    {
        $conditions = [];
        $params      = [];

        if ($from) { $conditions[] = "DATE(created_at) >= :from"; $params[':from'] = $from; }
        if ($to)   { $conditions[] = "DATE(created_at) <= :to";   $params[':to']   = $to; }

        if (!$conditions) return ['sql' => '', 'params' => []];
        $prefix = $extraAnd ? ' AND ' : ' WHERE ';
        return ['sql' => $prefix . implode(' AND ', $conditions), 'params' => $params];
    }

    private function appointmentDateRange(?string $from, ?string $to): array
    {
        $conditions = [];
        $params      = [];

        if ($from) { $conditions[] = "DATE(appointment_datetime) >= :from"; $params[':from'] = $from; }
        if ($to)   { $conditions[] = "DATE(appointment_datetime) <= :to";   $params[':to']   = $to; }

        $sql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return ['sql' => $sql, 'params' => $params];
    }

    private function formatPeriod(string $period): string
    {
        switch ($period) {
            case 'week':  return "YEARWEEK(created_at, 1)";
            case 'month': return "DATE_FORMAT(created_at, '%Y-%m')";
            default:      return "DATE(created_at)";
        }
    }

    private function formatPeriodAppointments(string $period): string
    {
        switch ($period) {
            case 'week':  return "YEARWEEK(appointment_datetime, 1)";
            case 'month': return "DATE_FORMAT(appointment_datetime, '%Y-%m')";
            default:      return "DATE(appointment_datetime)";
        }
    }
}
