<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/guestAppointmentDao.php';
require_once __DIR__ . '/../dao/appointmentDao.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/env.php';

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
     * Emails the guest a verification link containing a short-lived signed JWT.
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

        // Stateless, signed, 30-minute token. The frontend reads `sub` client-side
        // and sends the token back as a Bearer credential on subsequent requests.
        $token = Jwt::encode([
            'sub'     => $json['email'],
            'context' => 'guest',
        ], 1800);

        $frontendUrl = rtrim(Env::get('FRONTEND_URL', 'http://localhost:5173'), '/');
        $verifyUrl   = $frontendUrl . '/guest-appointments/new?token=' . $token;

        try {
            (new Mailer())->send(
                $json['email'],
                '[MediCode] Verifica tu correo para agendar tu cita',
                $this->buildVerificationTemplate($verifyUrl)
            );
        } catch (\Throwable) {
            // Fail silently to avoid revealing whether the email exists.
        }

        // The frontend never reads the token from this response (it uses the
        // email link); returned only in non-production to support automated tests.
        $data = Env::get('APP_ENV', 'production') !== 'production'
            ? ['verification_token' => $token]
            : null;
        jsonResponse('success', 'Si el correo es válido, recibirás un enlace de verificación.', $data);
    }

    private function buildVerificationTemplate(string $verifyUrl): string
    {
        $url = htmlspecialchars($verifyUrl);
        return '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
             . '<body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
             . '<div style="max-width:600px;margin:40px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">'
             . '<div style="background:#1a56db;padding:32px;text-align:center;"><h1 style="color:#fff;margin:0;font-size:24px;">MediCode UCR</h1></div>'
             . '<div style="padding:32px;color:#333;">'
             . '<p>Has solicitado agendar una cita como invitado/a.</p>'
             . '<p>Haz clic en el siguiente botón para verificar tu correo y continuar con tu solicitud. El enlace expira en <strong>30 minutos</strong>.</p>'
             . '<div style="text-align:center;margin:28px 0;">'
             . '<a href="' . $url . '" style="background:#1a56db;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">Verificar y agendar cita</a>'
             . '</div>'
             . '<p style="font-size:13px;color:#6b7280;">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>'
             . '<p style="font-size:12px;color:#1a56db;word-break:break-all;">' . $url . '</p>'
             . '<p style="font-size:14px;color:#6b7280;">Si no solicitaste esto, ignora este mensaje.</p>'
             . '</div>'
             . '<div style="padding:24px 32px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;">'
             . 'MediCode — Sistema de Citas Médicas UCR &bull; Correo automático, no responder.'
             . '</div></div></body></html>';
    }

    /**
     * POST /api/v1/guest-appointments
     * Creates a guest appointment. The verification JWT is supplied as a
     * Bearer token (from the email link); its `sub` claim is the guest email.
     */
    public function store(): void
    {
        $claims = getGuestClaims();
        if (!$claims) {
            jsonResponse('error', 'El enlace de verificación no es válido o ha expirado.', null, null, null, 401);
        }
        $guestEmail = $claims['sub'];

        $json    = getJsonInput();
        $areasStr = implode(',', $this->citaDao->getValidAreas());

        $errors = validar($json, [
            'full_name'            => 'required|max:255',
            'identifier'           => 'required|max:50|regex:/^\d{1}-\d{4}-\d{4}$/',
            'birth_date'           => 'required|date|before_today',
            'user_type'            => 'required|max:100',
            'phone'                => 'required|max:20|regex:/^(\+\d{1,3}\s)?\d{4}[\s\-]\d{4}$/',
            'attention_area'       => "required|in:{$areasStr}",
            'reason'               => 'nullable|max:1000',
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

        // Find or create patient (email comes from the verified JWT, not the body)
        $patientId = $this->dao->findOrCreatePatient([
            'full_name'  => $json['full_name'],
            'identifier' => $json['identifier'],
            'email'      => $guestEmail,
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

        jsonResponse('success', 'Solicitud de cita registrada correctamente.', $appointment, null, null, 201);
    }
}
