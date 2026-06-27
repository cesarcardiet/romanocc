<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Romanocc</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: background-color 0.2s ease;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 48px;
            width: 100%;
            max-width: 448px;
            position: relative;
            transition: all 0.2s ease;
        }

        /* Dark mode styles */
        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a;
            }
            
            .container {
                background: #1e293b;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
        }

        /* Force dark mode class */
        .dark body {
            background: #0f172a;
        }
        
        .dark .container {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-text {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            letter-spacing: -0.025em;
            transition: color 0.2s ease;
        }

        .title {
            text-align: center;
            color: #111827;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            transition: color 0.2s ease;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 32px;
            line-height: 1.5;
            transition: color 0.2s ease;
        }

        /* Dark mode text styles */
        @media (prefers-color-scheme: dark) {
            .logo-text {
                color: #ffffff;
            }
            
            .title {
                color: #ffffff;
            }
            
            .subtitle {
                color: #94a3b8;
            }
        }

        .dark .logo-text {
            color: #ffffff;
        }
        
        .dark .title {
            color: #ffffff;
        }
        
        .dark .subtitle {
            color: #94a3b8;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.2s ease;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s ease;
            background-color: #ffffff;
            color: #111827;
        }

        .form-control:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        /* Dark mode form styles */
        @media (prefers-color-scheme: dark) {
            .form-group label {
                color: #e2e8f0;
            }
            
            .form-control {
                border: 1px solid #475569;
                background-color: #334155;
                color: #ffffff;
            }
            
            .form-control::placeholder {
                color: #94a3b8;
            }
        }

        .dark .form-group label {
            color: #e2e8f0;
        }
        
        .dark .form-control {
            border: 1px solid #475569;
            background-color: #334155;
            color: #ffffff;
        }
        
        .dark .form-control::placeholder {
            color: #94a3b8;
        }

        .form-control.error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .error-message {
            color: #ef4444;
            font-size: 12px;
            margin-top: 4px;
            display: none;
            font-weight: 500;
        }

        .form-control.error:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .form-control.success {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-control.success:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .success-message-field {
            color: #10b981;
            font-size: 12px;
            margin-top: 4px;
            display: none;
            font-weight: 500;
        }

        .btn {
            width: 100%;
            padding: 12px 16px;
            background: #f97316;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
        }

        .btn:hover {
            background: #ea580c;
        }

        .btn:disabled {
            background: #64748b;
            cursor: not-allowed;
        }

        .success-message {
            background: #064e3b;
            color: #6ee7b7;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 24px;
            border: 1px solid #065f46;
            display: none;
        }

        .error-alert {
            background: #7f1d1d;
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 24px;
            border: 1px solid #991b1b;
            display: none;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            transition: color 0.2s ease;
        }

        .spinner {
            border: 3px solid #e5e7eb;
            border-top: 3px solid #f97316;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        /* Dark mode loading styles */
        @media (prefers-color-scheme: dark) {
            .loading {
                color: #e2e8f0;
            }
            
            .spinner {
                border: 3px solid #475569;
            }
        }

        .dark .loading {
            color: #e2e8f0;
        }
        
        .dark .spinner {
            border: 3px solid #475569;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 640px) {
            .container {
                padding: 24px;
                margin: 16px;
                max-width: calc(100% - 32px);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <div class="logo-text">Romanocc</div>
        </div>

        <h1 class="title">Restablecer Contraseña</h1>
        <p class="subtitle">Ingresa tu nueva contraseña para completar el proceso</p>

        <div class="success-message" id="successMessage">
            ¡Contraseña actualizada exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.
        </div>

        <div class="error-alert" id="errorAlert">
            <span id="errorText"></span>
        </div>

        <form id="resetPasswordForm">
            <div class="form-group">
                <label for="password">Nueva Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    required 
                    minlength="6"
                    placeholder="Ingresa tu nueva contraseña"
                >
                <div class="error-message" id="passwordError"></div>
                <div class="success-message-field" id="passwordSuccess">✓ Contraseña válida</div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmar Nueva Contraseña</label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    class="form-control" 
                    required 
                    minlength="6"
                    placeholder="Confirma tu nueva contraseña"
                >
                <div class="error-message" id="passwordConfirmationError"></div>
                <div class="success-message-field" id="passwordConfirmationSuccess">✓ Las contraseñas coinciden</div>
            </div>

            <button type="submit" class="btn" id="submitBtn">
                Actualizar Contraseña
            </button>
        </form>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Actualizando contraseña...</p>
        </div>
    </div>

    <script>
        // Theme detection and application
        function applyTheme() {
            const isDark = localStorage.getItem('theme') === 'dark' || 
                          (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
            
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        // Apply theme on load
        applyTheme();

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyTheme);

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetPasswordForm');
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            const successMessage = document.getElementById('successMessage');
            const errorAlert = document.getElementById('errorAlert');
            const errorText = document.getElementById('errorText');

            // Obtener token de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');

            if (!token) {
                showError('Enlace inválido o expirado. Por favor, solicita un nuevo enlace de recuperación.');
                form.style.display = 'none';
                return;
            }

            // Validación en tiempo real
            const passwordField = document.getElementById('password');
            const passwordConfirmationField = document.getElementById('password_confirmation');

            passwordField.addEventListener('input', function() {
                validatePasswords();
            });

            passwordConfirmationField.addEventListener('input', function() {
                validatePasswords();
            });

            // Validación adicional cuando el usuario sale del campo de confirmación
            passwordConfirmationField.addEventListener('blur', function() {
                const password = passwordField.value;
                const passwordConfirmation = passwordConfirmationField.value;
                
                if (passwordConfirmation.length > 0 && password !== passwordConfirmation) {
                    showFieldError('password_confirmation', 'Las contraseñas no coinciden');
                } else if (passwordConfirmation.length > 0 && password === passwordConfirmation && password.length >= 6) {
                    showFieldSuccess('password_confirmation');
                }
            });

            function validatePasswords() {
                const password = passwordField.value;
                const passwordConfirmation = passwordConfirmationField.value;

                // Limpiar errores y éxitos previos
                clearFieldError('password');
                clearFieldError('password_confirmation');
                clearFieldSuccess('password');
                clearFieldSuccess('password_confirmation');

                let isValid = true;

                // Validar longitud mínima de contraseña
                if (password.length > 0) {
                    if (password.length < 6) {
                        showFieldError('password', 'La contraseña debe tener al menos 6 caracteres');
                        isValid = false;
                    } else {
                        showFieldSuccess('password');
                    }
                }

                // Validar coincidencia de contraseñas
                if (passwordConfirmation.length > 0) {
                    if (password !== passwordConfirmation) {
                        showFieldError('password_confirmation', 'Las contraseñas no coinciden');
                        isValid = false;
                    } else if (password.length >= 6) {
                        showFieldSuccess('password_confirmation');
                    }
                }

                return isValid;
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const password = document.getElementById('password').value;
                const passwordConfirmation = document.getElementById('password_confirmation').value;

                // Limpiar solo alertas generales, no errores de campos
                errorAlert.style.display = 'none';
                successMessage.style.display = 'none';

                // Validaciones
                let hasErrors = false;

                if (password.length < 6) {
                    showFieldError('password', 'La contraseña debe tener al menos 6 caracteres');
                    showError('La contraseña debe tener al menos 6 caracteres');
                    hasErrors = true;
                }

                if (password !== passwordConfirmation) {
                    showFieldError('password_confirmation', 'Las contraseñas no coinciden');
                    showError('Por favor, asegúrate de que ambas contraseñas sean idénticas');
                    hasErrors = true;
                }

                if (hasErrors) {
                    return;
                }

                // Mostrar loading
                showLoading(true);

                try {
                    const response = await fetch('/api/auth/reset-password', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            token: token,
                            password: password,
                            password_confirmation: passwordConfirmation
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        showSuccess();
                        form.style.display = 'none';
                    } else {
                        if (data.errors) {
                            // Mostrar errores de validación
                            Object.keys(data.errors).forEach(field => {
                                showFieldError(field, data.errors[field][0]);
                            });
                        } else {
                            showError(data.message || 'Error al actualizar la contraseña');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError('Error de conexión. Por favor, intenta nuevamente.');
                } finally {
                    showLoading(false);
                }
            });

            function showLoading(show) {
                loading.style.display = show ? 'block' : 'none';
                submitBtn.disabled = show;
            }

            function showSuccess() {
                successMessage.style.display = 'block';
                errorAlert.style.display = 'none';
            }

            function showError(message) {
                errorText.textContent = message;
                errorAlert.style.display = 'block';
                successMessage.style.display = 'none';
            }

            function showFieldError(fieldName, message) {
                const field = document.getElementById(fieldName);
                const errorElement = document.getElementById(fieldName + 'Error');
                const successElement = document.getElementById(fieldName + 'Success');
                
                if (field && errorElement) {
                    field.classList.add('error');
                    field.classList.remove('success');
                    errorElement.textContent = message;
                    errorElement.style.display = 'block';
                    
                    // Ocultar mensaje de éxito si existe
                    if (successElement) {
                        successElement.style.display = 'none';
                    }
                }
            }

            function clearFieldError(fieldName) {
                const field = document.getElementById(fieldName);
                const errorElement = document.getElementById(fieldName + 'Error');
                
                if (field && errorElement) {
                    field.classList.remove('error');
                    errorElement.style.display = 'none';
                }
            }

            function showFieldSuccess(fieldName) {
                const field = document.getElementById(fieldName);
                const successElement = document.getElementById(fieldName + 'Success');
                
                if (field && successElement) {
                    field.classList.add('success');
                    field.classList.remove('error');
                    successElement.style.display = 'block';
                }
            }

            function clearFieldSuccess(fieldName) {
                const field = document.getElementById(fieldName);
                const successElement = document.getElementById(fieldName + 'Success');
                
                if (field && successElement) {
                    field.classList.remove('success');
                    successElement.style.display = 'none';
                }
            }

            function clearErrors() {
                // Limpiar errores de campos
                document.querySelectorAll('.form-control').forEach(field => {
                    field.classList.remove('error', 'success');
                });
                document.querySelectorAll('.error-message').forEach(error => {
                    error.style.display = 'none';
                });
                document.querySelectorAll('.success-message-field').forEach(success => {
                    success.style.display = 'none';
                });
                
                // Limpiar alertas
                errorAlert.style.display = 'none';
                successMessage.style.display = 'none';
            }
        });
    </script>
</body>
</html>
