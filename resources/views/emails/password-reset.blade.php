<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña - Romanocc</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        .email-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .content {
            padding: 30px 20px;
        }
        
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .message {
            font-size: 16px;
            margin-bottom: 25px;
            color: #555;
            line-height: 1.6;
        }
        
        .reset-button {
            display: inline-block;
            background: #E87700;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: background-color 0.3s ease;
        }
        
        .reset-button:hover {
            background: #d66a00;
        }
        
        .security-notice {
            background: #f8f9fa;
            border-left: 4px solid #E87700;
            padding: 15px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .security-notice h3 {
            margin: 0 0 10px 0;
            color: #E87700;
            font-size: 16px;
        }
        
        .security-notice p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .footer p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        
        .logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .alternative-link {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #666;
        }
        
        .alternative-link strong {
            color: #333;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .header, .content, .footer {
                padding: 20px 15px;
            }
            
            .reset-button {
                display: block;
                text-align: center;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ asset('romanoc-logo.png') }}" alt="Romanocc Logo" class="logo" onerror="this.style.display='none'">
            <h1>Recuperación de Contraseña</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hola {{ $user->name ?? 'Usuario' }},
            </div>
            
            <div class="message">
                Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en Romanocc. 
                Si no solicitaste este cambio, puedes ignorar este correo de forma segura.
            </div>
            
            <div class="message">
                Para restablecer tu contraseña, haz clic en el siguiente botón:
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="reset-button">
                    Restablecer Contraseña
                </a>
            </div>
            
            <div class="alternative-link">
                <strong>¿No puedes hacer clic en el botón?</strong><br>
                Copia y pega el siguiente enlace en tu navegador:<br>
                <a href="{{ $resetUrl }}" style="color: #E87700; word-break: break-all;">{{ $resetUrl }}</a>
            </div>
            
            <div class="security-notice">
                <h3>🔒 Información de Seguridad</h3>
                <p>
                    • Este enlace expirará en 24 horas por motivos de seguridad<br>
                    • Solo podrás usar este enlace una vez<br>
                    • Si no solicitaste este cambio, ignora este correo<br>
                    • Tu contraseña actual seguirá funcionando hasta que la cambies
                </p>
            </div>
            
            <div class="message">
                Si tienes problemas para acceder a tu cuenta o necesitas ayuda, 
                no dudes en contactarnos.
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Romanocc</strong></p>
            <p>Ley General de Contrataciones Públicas y Reglamentos</p>
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>
