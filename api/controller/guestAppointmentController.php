<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/guestAppointmentDao.php';
require_once __DIR__ . '/../dao/appointmentDao.php';

class GuestAppointmentController
{
    private GuestAppointmentDao $dao;
    private AppointmentDao $citaDao;

    public function __construct()
    {
        $this->dao     = new GuestAppointmentDao();
        $this->citaDao = new AppointmentDao();
    }

    /**
     * POST /api/v1/guest-appointments/start
     * Sends a verification token to the guest's email address.
     */
    public function start(): void
    {
        $json    = getJsonInput();
        $errors = validar($json, ['email' => 'required|email|max:254']);

        // Only institutional email addresses (@ucr.ac.cr) are accepted
        if (empty($errors['email'])) {
            $dominio = substr(strrchr($json['email'] ?? '', '@'), 1);
            if ($dominio !== 'ucr.ac.cr') {
                $errors['email'][] = 'Solo se aceptan correos institucionales (@ucr.ac.cr).';
            }
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $token = bin2hex(random_bytes(16));
        $this->dao->saveVerificationToken($json['email'], $token);

        jsonResponse('success', 'Si el correo es válido, recibirás un código de verificación.', [
            'email'              => $json['email'],
            'verification_token' => $token,
        ]);
    }

    /**
     * POST /api/v1/guest-appointments
     * Creates a guest appointment using the verification token.
     */
    public function store(): void
    {
        $json    = getJsonInput();
        $areasStr = implode(',', $this->citaDao->getValidAreas());

        $errors = validar($json, [
            'verification_token'   => 'required',
            'full_name'            => 'required|max:255',
            'identifier'           => 'required|max:50|regex:/^\d{1}-\d{4}-\d{4}$/',
            'birth_date'           => 'required|date|before_today',
            'user_type'            => 'required|max:100',
            'phone'                => 'required|max:20|regex:/^(\+\d{1,3}\s)?\d{4}[\s\-]\d{4}$/',
            'attention_area'       => "required|in:{$areasStr}",
            'reason'               => 'required|max:1000',
            'appointment_datetime' => 'required|after_now',
        ]);

        // Validate companion if partial companion data is provided
        $tieneAcompanante = !empty($json['companion_identifier']) || !empty($json['companion_name']);
        if ($tieneAcompanante) {
            if (empty($json['companion_identifier'])) {
                $errors['companion_identifier'][] = 'El campo companion_identifier es obligatorio al registrar acompañante.';
            }
            if (empty($json['companion_name'])) {
                $errors['companion_name'][] = 'El campo companion_name es obligatorio al registrar acompañante.';
            }
            if (empty($json['companion_phone'])) {
                $errors['companion_phone'][] = 'El campo companion_phone es obligatorio al registrar acompañante.';
            }
        }

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        // Verify token
        $record = $this->dao->findVerificationToken($json['verification_token']);
        if (!$record) {
            jsonResponse('error', 'El token de verificación no es válido o ha expirado.', null, null, null, 422);
        }

        // Expiry: 30 minutes
        if (strtotime($record['created_at']) + 1800 < time()) {
            $this->dao->deleteVerificationToken($json['verification_token']);
            jsonResponse('error', 'El token de verificación ha expirado.', null, null, null, 422);
        }

        // Find or create patient
        $patientId = $this->dao->findOrCreatePatient([
            'full_name'  => $json['full_name'],
            'identifier' => $json['identifier'],
            'email'      => $record['email'],
            'birth_date' => $json['birth_date'],
            'user_type'  => $json['user_type'],
            'phone'      => $json['phone'],
        ]);

        // Find or create companion
        $companionId = null;
        if ($tieneAcompanante) {
            $companionId = $this->dao->findOrCreateCompanion([
                'full_name'  => $json['companion_name'],
                'identifier' => $json['companion_identifier'],
                'phone'      => $json['companion_phone'],
            ]);
        }

        $citaId = $this->dao->createAppointment($patientId, $companionId, $json);
        $appointment   = $this->dao->findAppointmentById($citaId);

        $this->dao->deleteVerificationToken($json['verification_token']);

        jsonResponse('success', 'Solicitud de cita registrada correctamente.', $appointment, null, null, 201);
    }
}
