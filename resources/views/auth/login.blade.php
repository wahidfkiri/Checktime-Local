<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Modern App</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --gradient-start: #198754;
            --gradient-end: #4ba262ff;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-light) 0%, #f1f5f9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-dark);
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
        }
        
        .login-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: transform 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
        }
        
        .login-header {
            /* background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%); */
            padding: 15px 15px;
            text-align: center;
            color: black;
        }
        
        .login-logo {
            font-size: 2.5rem;
            margin-bottom: 16px;
            display: flex;
            justify-content: center;
        }
        
        .login-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
            color: black;
        }
        
        .login-body {
            padding: 15px 32px;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 14px;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            z-index: 10;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 15px;
            font-weight: 400;
            transition: all 0.3s;
            background-color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }
        
        .form-control.error {
            border-color: #ef4444;
        }
        
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            z-index: 10;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .checkbox-container input {
            display: none;
        }
        
        .checkmark {
            width: 18px;
            height: 18px;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .checkbox-container input:checked + .checkmark {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .checkbox-container input:checked + .checkmark:after {
            content: '✓';
            color: white;
            font-size: 12px;
        }
        
        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .login-btn {
            width: 100%;
            padding: 16px;
            border: none;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-btn:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 32px;
            color: var(--text-light);
            font-size: 14px;
        }
        
        .register-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .register-link:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .error-message {
            color: #dc2626;
            font-size: 13px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 10px;
            }
            
            .login-header, .login-body {
                padding: 30px 24px;
            }
            
            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="{{ asset('logo.jpg') }}" alt="Logo" width="150" height="100">
                </div>
                <p class="login-subtitle">Connectez-vous à votre compte</p>
            </div>
            
            <div class="login-body">
                <div id="alert-container"></div>
                
                <form id="loginForm">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Adresse Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope input-icon"></i>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="votre@email.com"
                                required
                            >
                        </div>
                        <div id="email-error" class="error-message"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Mot de passe</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Votre mot de passe"
                                required
                            >
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="password-error" class="error-message"></div>
                    </div>
                    
                    <div class="remember-forgot">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember" id="remember">
                            <span class="checkmark"></span>
                            Se souvenir de moi
                        </label>
                        <a href="{{ route('password.request') }}" class="forgot-link" id="">Mot de passe oublié ?</a>
                    </div>
                    
                    <button type="submit" class="login-btn" id="submitBtn">
                        <span id="btnText">Se connecter</span>
                        <div class="spinner" id="spinner" style="display: none;"></div>
                    </button>
                </form>
                
                
            </div>
        </div>
    </div>

    <!-- jQuery (pour Ajax) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('#togglePassword').click(function() {
                const passwordInput = $('#password');
                const icon = $(this).find('i');
                
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
            
            // Form submission
            $('#loginForm').submit(function(e) {
                e.preventDefault();
                
                // Reset errors
                $('.error-message').empty();
                $('.form-control').removeClass('error');
                $('#alert-container').empty();
                
                // Show loading state
                $('#submitBtn').prop('disabled', true);
                $('#btnText').text('Connexion...');
                $('#spinner').show();
                
                // Get form data
                const formData = $(this).serialize();
                
                // Send Ajax request
                $.ajax({
                    url: '{{ route("login.post") }}',
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', response.message);
                            
                            // Redirect after delay
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        $('#submitBtn').prop('disabled', false);
                        $('#btnText').text('Se connecter');
                        $('#spinner').hide();
                        
                        if (xhr.status === 422) {
                            // Validation errors
                            const errors = xhr.responseJSON.errors;
                            for (const field in errors) {
                                $(`#${field}-error`).html(
                                    `<i class="fas fa-exclamation-circle"></i> ${errors[field][0]}`
                                );
                                $(`#${field}`).addClass('error');
                            }
                        } else if (xhr.status === 401) {
                            // Authentication error
                            showAlert('danger', xhr.responseJSON.message);
                        }else if(xhr.status === 403) {
                            showAlert('danger','Votre compte est désactivé. Contactez l\'administration.');
                        } else {
                            // General error
                            showAlert('danger', 'Une erreur est survenue. Veuillez réessayer.');
                        }
                    }
                });
            });
            
            // Helper function to show alerts
            function showAlert(type, message) {
                const alertClass = type === 'danger' ? 'alert-danger' : 'alert-success';
                const icon = type === 'danger' ? 'fa-exclamation-triangle' : 'fa-check-circle';
                
                const alertHtml = `
                    <div class="alert ${alertClass}">
                        <i class="fas ${icon}"></i>
                        <div>${message}</div>
                    </div>
                `;
                
                $('#alert-container').html(alertHtml);
                
                // Auto-remove success alerts after 5 seconds
                if (type === 'success') {
                    setTimeout(() => {
                        $('#alert-container').empty();
                    }, 5000);
                }
            }
            
            // Input validation on blur
            $('.form-control').blur(function() {
                const field = $(this);
                const fieldName = field.attr('id');
                const errorElement = $(`#${fieldName}-error`);
                
                errorElement.empty();
                field.removeClass('error');
                
                // Simple validation
                if (field.val().trim() === '') {
                    errorElement.html('<i class="fas fa-exclamation-circle"></i> Ce champ est requis');
                    field.addClass('error');
                } else if (fieldName === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(field.val())) {
                        errorElement.html('<i class="fas fa-exclamation-circle"></i> Email invalide');
                        field.addClass('error');
                    }
                }
            });
        });
    </script>
</body>
</html>