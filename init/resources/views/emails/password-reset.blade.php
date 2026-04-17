<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablece tu contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        h2 {
            color: #333333;
            margin-top: 0;
        }
        p {
            color: #555555;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            margin: 24px 0;
            padding: 14px 32px;
            background-color: #0f172a;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 15px;
            letter-spacing: 0.5px;
            border: 2px solid #0f172a;
        }
        .footer {
            margin-top: 32px;
            font-size: 12px;
            color: #999999;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Solicitud de restablecimiento de contraseña</h2>
        <p>Recibes este correo porque recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>
        <p>Haz clic en el botón de abajo para restablecer tu contraseña. Este enlace expirará en <strong>{{ config('auth.passwords.users.expire') }} minutos</strong>.</p>

        <a href="{{ $resetUrl }}" class="btn">Restablecer contraseña</a>

        <p>Si no solicitaste restablecer tu contraseña, no es necesario realizar ninguna otra acción.</p>

        <p>Si el botón anterior no funciona, copia y pega la siguiente URL en tu navegador:</p>
        <p style="word-break: break-all;">{{ $resetUrl }}</p>

        <div class="footer">
            <p>Este es un mensaje automático, por favor no respondas.</p>
        </div>
    </div>
</body>
</html>
