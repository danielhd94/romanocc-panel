<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - RomanoCC</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #FDFDFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(232, 119, 0, 0.02) 0%, rgba(232, 119, 0, 0.05) 100%);
            pointer-events: none;
            z-index: -1;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(27, 27, 24, 0.1);
            border: 1px solid rgba(27, 27, 24, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            max-width: 80px;
            height: auto;
        }

        .title {
            text-align: center;
            color: #1b1b18;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #706f6c;
            font-size: 14px;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #1b1b18;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(27, 27, 24, 0.1);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            background-color: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #E87700;
            box-shadow: 0 0 0 3px rgba(232, 119, 0, 0.1);
        }

        .form-control.error {
            border-color: #F53003;
            box-shadow: 0 0 0 3px rgba(245, 48, 3, 0.1);
        }

        .error-message {
            color: #F53003;
            font-size: 12px;
            margin-top: 4px;
            display: none;
            font-weight: 500;
        }

        .form-control.error:focus {
            border-color: #F53003;
            box-shadow: 0 0 0 3px rgba(245, 48, 3, 0.2);
        }

        .form-control.success {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .form-control.success:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }

        .success-message-field {
            color: #28a745;
            font-size: 12px;
            margin-top: 4px;
            display: none;
            font-weight: 500;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #E87700;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        .btn:hover {
            background: #d66a00;
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            display: none;
        }

        .error-alert {
            background: #fff2f2;
            color: #F53003;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(245, 48, 3, 0.2);
            display: none;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #E87700;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #E87700;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ asset('images/logo.png') }}" alt="RomanoCC Logo" onerror="this.style.display='none'">
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

        <div class="back-link">
            <a href="{{ url('/') }}">← Volver al inicio</a>
        </div>
    </div>

    <script>
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
