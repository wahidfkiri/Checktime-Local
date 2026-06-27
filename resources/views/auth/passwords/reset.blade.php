<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe - {{ config('app.name') }}</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #198754;
            --primary-dark: #4ba262ff;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --error-color: #ef4444;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-light) 0%, #f1f5f9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .reset-container {
            width: 100%;
            max-width: 500px;
        }
        
        .reset-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }
        
        .reset-header {
            /* background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); */
            padding: 40px 30px 30px; 
            text-align: center;
            color: black;
        }
        
        .reset-logo {
            margin-bottom: 20px;
        }
        
        .reset-logo img {
            max-width: 120px;
            height: auto;
            background: white;
            padding: 10px;
            border-radius: 10px;
        }
        
        .reset-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .reset-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .reset-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            z-index: 2;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.1);
            outline: none;
        }
        
        .form-control.is-invalid {
            border-color: var(--error-color);
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            z-index: 2;
        }
        
        .toggle-password:hover {
            color: var(--primary-color);
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-text {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 5px;
        }
        
        .strength-bar {
            height: 5px;
            background-color: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .strength-progress {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background-color: #ef4444; width: 25%; }
        .strength-fair { background-color: #f59e0b; width: 50%; }
        .strength-good { background-color: #3b82f6; width: 75%; }
        .strength-strong { background-color: #10b981; width: 100%; }
        
        .requirements {
            margin-top: 10px;
            font-size: 12px;
            color: var(--text-light);
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        
        .requirement i {
            font-size: 10px;
            width: 14px;
        }
        
        .requirement.met {
            color: var(--success-color);
        }
        
        .requirement.unmet {
            color: var(--text-light);
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.2);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 25px;
            color: var(--text-light);
            font-size: 14px;
        }
        
        .back-to-login a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
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
        
        .alert-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #3b82f6;
        }
        
        .invalid-feedback {
            color: var(--error-color);
            font-size: 13px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .spinner-sm {
            width: 16px;
            height: 16px;
            border-width: 2px;
        }
        
        /* Success state */
        .success-state {
            text-align: center;
            padding: 50px 40px;
        }
        
        .success-icon {
            font-size: 80px;
            color: var(--success-color);
            margin-bottom: 30px;
        }
        
        .success-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--text-dark);
        }
        
        .success-message {
            color: var(--text-light);
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .login-btn {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .login-btn:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.2);
        }
        
        @media (max-width: 576px) {
            .reset-body, .reset-header {
                padding: 30px 20px;
            }
            
            .reset-title {
                font-size: 24px;
            }
            
            .success-state {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            @if(session('status'))
                <!-- Success State -->
                <div class="success-state">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="success-title">Mot de passe réinitialisé !</h2>
                    <p class="success-message">
                        Votre mot de passe a été changé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
                    </p>
                    <a href="{{ route('login') }}" class="login-btn">
                        <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                    </a>
                </div>
            @else
                <!-- Reset Form -->
                <div class="reset-header">
                    <div class="reset-logo">
                        <img src="{{ asset('logo.jpg') }}" alt="Logo">
                    </div>
                    <h1 class="reset-title">Nouveau mot de passe</h1>
                    <p class="reset-subtitle">Choisissez un mot de passe sécurisé</p>
                </div>
                
                <div class="reset-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                @foreach($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                    @endif
                    
                    <form id="resetPasswordForm" method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Adresse Email</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope input-icon"></i>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="form-control @error('email') is-invalid @enderror" 
                                    value="{{ $email ?? old('email') }}"
                                    placeholder="votre@email.com"
                                    required
                                    {{ $email ? 'readonly' : '' }}
                                >
                            </div>
                            @error('email')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password">Nouveau mot de passe</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock input-icon"></i>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-control @error('password') is-invalid @enderror" 
                                    placeholder="Votre nouveau mot de passe"
                                    required
                                >
                                <button type="button" class="toggle-password" data-target="password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            
                            <!-- Password strength indicator -->
                            <div class="password-strength"  style="display: none;">
                                <div class="strength-text">Force du mot de passe : <span id="strengthText">Faible</span></div>
                                <div class="strength-bar">
                                    <div class="strength-progress" id="strengthProgress"></div>
                                </div>
                            </div>
                            
                            <!-- Password requirements -->
                            <div class="requirements" style="display:none;">
                                <div class="requirement unmet" id="req-length">
                                    <i class="fas fa-circle"></i>
                                    <span>Minimum 8 caractères</span>
                                </div>
                                <div class="requirement unmet" id="req-uppercase">
                                    <i class="fas fa-circle"></i>
                                    <span>Au moins une majuscule</span>
                                </div>
                                <div class="requirement unmet" id="req-lowercase">
                                    <i class="fas fa-circle"></i>
                                    <span>Au moins une minuscule</span>
                                </div>
                                <div class="requirement unmet" id="req-number">
                                    <i class="fas fa-circle"></i>
                                    <span>Au moins un chiffre</span>
                                </div>
                                <div class="requirement unmet" id="req-special">
                                    <i class="fas fa-circle"></i>
                                    <span>Au moins un caractère spécial</span>
                                </div>
                            </div>
                            
                            @error('password')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock input-icon"></i>
                                <input 
                                    type="password" 
                                    id="password_confirmation" 
                                    name="password_confirmation" 
                                    class="form-control @error('password_confirmation') is-invalid @enderror" 
                                    placeholder="Confirmer votre mot de passe"
                                    required
                                >
                                <button type="button" class="toggle-password" data-target="password_confirmation">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="submit-btn" id="submitBtn">
                            <span id="btnText">Réinitialiser le mot de passe</span>
                            <div class="spinner" id="spinner" style="display: none;"></div>
                        </button>
                    </form>
                    
                    <div class="back-to-login">
                        Vous vous souvenez de votre mot de passe ?
                        <a href="{{ route('login') }}">Se connecter</a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialisation
            const form = $('#resetPasswordForm');
            const passwordInput = $('#password');
            const confirmInput = $('#password_confirmation');
            const submitBtn = $('#submitBtn');
            const btnText = $('#btnText');
            const spinner = $('#spinner');
            
            // Toggle password visibility
            $('.toggle-password').click(function() {
                const targetId = $(this).data('target');
                const input = $(`#${targetId}`);
                const icon = $(this).find('i');
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
            
            // Password strength checker
            passwordInput.on('input', function() {
                const password = $(this).val();
                checkPasswordStrength(password);
                validatePasswordRequirements(password);
            });
            
            // Form validation before submission
            form.on('submit', function(e) {
                // Prevent default if not valid
                if (!validateForm()) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading state
                submitBtn.prop('disabled', true);
                btnText.text('Traitement...');
                spinner.show();
                
                return true;
            });
            
            // Validation functions
            function validateForm() {
                let isValid = true;
                
                // Validate password
                const password = passwordInput.val();
                if (!validatePassword(password)) {
                    isValid = false;
                }
                
                // Validate confirmation
                const confirmation = confirmInput.val();
                if (password !== confirmation) {
                    confirmInput.addClass('is-invalid');
                    confirmInput.after('<div class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> Les mots de passe ne correspondent pas</div>');
                    isValid = false;
                } else {
                    confirmInput.removeClass('is-invalid');
                    confirmInput.next('.invalid-feedback').remove();
                }
                
                return isValid;
            }
            
            function validatePassword(password) {
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };
                
                const allMet = Object.values(requirements).every(req => req);
                
                if (!allMet) {
                    passwordInput.addClass('is-invalid');
                    return false;
                }
                
                passwordInput.removeClass('is-invalid');
                return true;
            }
            
            function checkPasswordStrength(password) {
                if (password.length === 0) {
                    $('#passwordStrength').hide();
                    return;
                }
                
                $('#passwordStrength').show();
                
                let strength = 0;
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };
                
                // Calculate strength
                Object.values(requirements).forEach(req => {
                    if (req) strength++;
                });
                
                // Update UI
                const strengthClasses = ['strength-weak', 'strength-fair', 'strength-good', 'strength-strong'];
                const strengthLabels = ['Faible', 'Moyen', 'Bon', 'Fort'];
                
                strengthProgress
                    .removeClass('strength-weak strength-fair strength-good strength-strong')
                    .addClass(strengthClasses[strength - 1] || 'strength-weak');
                
                strengthText.text(strengthLabels[strength - 1] || 'Faible');
            }
            
            function validatePasswordRequirements(password) {
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };
                
                // Update requirement indicators
                updateRequirement('req-length', requirements.length);
                updateRequirement('req-uppercase', requirements.uppercase);
                updateRequirement('req-lowercase', requirements.lowercase);
                updateRequirement('req-number', requirements.number);
                updateRequirement('req-special', requirements.special);
            }
            
            function updateRequirement(elementId, isMet) {
                const element = $(`#${elementId}`);
                const icon = element.find('i');
                
                if (isMet) {
                    element.removeClass('unmet').addClass('met');
                    icon.removeClass('fa-circle').addClass('fa-check-circle');
                } else {
                    element.removeClass('met').addClass('unmet');
                    icon.removeClass('fa-check-circle').addClass('fa-circle');
                }
            }
            
            // Auto-focus password field if email is pre-filled
            if (passwordInput.val() === '' && $('#email').val()) {
                passwordInput.focus();
            }
        });
    </script>
</body>
</html>