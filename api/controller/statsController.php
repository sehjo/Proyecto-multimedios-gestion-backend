<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/statsDao.php';

class StatsController
{
    private $dao;

    public function __construct()
    {
        $this->dao = new StatsDao();
    }

    // ---- Patients ----

    public function totalPatients(): void
    {
        requireAuth();
        $from    = $_GET['from'] ?? null;
        $to      = $_GET['to']   ?? null;
        $errors = [];

        if ($from !== null) {
            $d = DateTime::createFromFormat('Y-m-d', $from);
            if (!$d || $d->format('Y-m-d') !== $from) {
                $errors['from'][] = 'El parámetro from debe tener formato YYYY-MM-DD.';
            }
        }
        if ($to !== null) {
            $d = DateTime::createFromFormat('Y-m-d', $to);
            if (!$d || $d->format('Y-m-d') !== $to) {
                $errors['to'][] = 'El parámetro to debe tener formato YYYY-MM-DD.';
            }
        }
        if (empty($errors) && $from !== null && $to !== null && $to < $from) {
            $errors['to'][] = 'El parámetro to no puede ser anterior a from.';
        }
        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        jsonResponse('success', 'Total de pacientes obtenido correctamente.', $this->dao->totalPatients($from, $to));
    }

    public function minorPatients(): void
    {
        requireAuth();
        $from = $_GET['from'] ?? null;
        $to   = $_GET['to']   ?? null;
        jsonResponse('success', 'Desglose de pacientes por edad obtenido correctamente.', $this->dao->underagePatients($from, $to));
    }

    public function patientsByType(): void
    {
        requireAuth();
        jsonResponse('success', 'Pacientes por tipo obtenidos correctamente.', $this->dao->patientsByType());
    }

    public function patientsByPeriod(): void
    {
        requireAuth();

        $dateFrom = $_GET['date_from'] ?? $_GET['from'] ?? null;
        $dateTo   = $_GET['date_to']   ?? $_GET['to']   ?? null;
        $period   = $_GET['period']    ?? 'day';
        $errors  = [];

        if (!$dateFrom || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $errors['date_from'][] = 'El parámetro date_from es obligatorio (YYYY-MM-DD).';
        }
        if (!$dateTo || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $errors['date_to'][] = 'El parámetro date_to es obligatorio (YYYY-MM-DD).';
        }
        if (!in_array($period, ['day', 'week', 'month'], true)) {
            $errors['period'][] = 'El parámetro period debe ser: day, week o month.';
        }
        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        jsonResponse('success', 'Pacientes por período obtenidos correctamente.', $this->dao->patientsByPeriod($dateFrom, $dateTo, $period));
    }

    // ---- Appointments ----

    public function appointmentsByStatus(): void
    {
        requireAuth();
        $from = $_GET['from'] ?? null;
        $to   = $_GET['to']   ?? null;
        jsonResponse('success', 'Citas por estado obtenidas correctamente.', $this->dao->appointmentsByStatus($from, $to));
    }

    public function appointmentsByArea(): void
    {
        requireAuth();
        $from = $_GET['from'] ?? null;
        $to   = $_GET['to']   ?? null;
        jsonResponse('success', 'Citas por área obtenidas correctamente.', $this->dao->appointmentsByArea($from, $to));
    }

    public function appointmentsByPeriod(): void
    {
        requireAuth();

        $dateFrom = $_GET['date_from'] ?? $_GET['from'] ?? null;
        $dateTo   = $_GET['date_to']   ?? $_GET['to']   ?? null;
        $period   = $_GET['period']    ?? 'day';
        $errors  = [];

        if (!$dateFrom || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $errors['date_from'][] = 'El parámetro date_from es obligatorio (YYYY-MM-DD).';
        }
        if (!$dateTo || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $errors['date_to'][] = 'El parámetro date_to es obligatorio (YYYY-MM-DD).';
        }
        if (!in_array($period, ['day', 'week', 'month'], true)) {
            $errors['period'][] = 'El parámetro period debe ser: day, week o month.';
        }
        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        jsonResponse('success', 'Citas por período obtenidas correctamente.', $this->dao->appointmentsByPeriod($dateFrom, $dateTo, $period));
    }

    public function cancellations(): void
    {
        requireAuth();
        $from = $_GET['from'] ?? null;
        $to   = $_GET['to']   ?? null;
        jsonResponse('success', 'Estadísticas de cancellations obtenidas correctamente.', $this->dao->cancellations($from, $to));
    }

    // ---- Users ----

    public function usersByStatus(): void
    {
        requireAuth();
        jsonResponse('success', 'Usuarios por estado obtenidos correctamente.', $this->dao->usersByStatus());
    }

    public function usersByRole(): void
    {
        requireAuth();
        jsonResponse('success', 'Usuarios por rol obtenidos correctamente.', $this->dao->usersByRole());
    }
}
