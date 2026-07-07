<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Installation - CheckTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --success: #10b981;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .installer-container { width: 100%; max-width: 800px; }
        .installer-header { text-align: center; margin-bottom: 30px; color: white; }
        .installer-header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .installer-header p { opacity: 0.85; font-size: 1.05rem; }
        .installer-card { background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
        .progress-steps { display: flex; justify-content: center; padding: 25px 20px 15px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .step-indicator { display: flex; align-items: center; gap: 8px; position: relative; }
        .step-indicator:not(:last-child)::after { content: ''; width: 40px; height: 2px; background: #e2e8f0; margin: 0 10px; }
        .step-indicator.active:not(:last-child)::after { background: var(--primary); }
        .step-indicator.completed:not(:last-child)::after { background: var(--success); }
        .step-circle { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.85rem; border: 2px solid #e2e8f0; color: #94a3b8; background: white; transition: all 0.3s ease; }
        .step-indicator.active .step-circle { border-color: var(--primary); background: var(--primary); color: white; }
        .step-indicator.completed .step-circle { border-color: var(--success); background: var(--success); color: white; }
        .step-label { font-size: 0.75rem; color: #94a3b8; font-weight: 500; display: none; }
        @media (min-width: 640px) { .step-label { display: block; } .step-indicator:not(:last-child)::after { width: 50px; } }
        .step-indicator.active .step-label { color: var(--primary); }
        .step-indicator.completed .step-label { color: var(--success); }
        .form-content { padding: 30px 35px; min-height: 380px; }
        .step-panel { display: none; animation: fadeIn 0.3s ease; }
        .step-panel.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .step-panel h3 { font-size: 1.3rem; font-weight: 600; color: #1e293b; margin-bottom: 5px; }
        .step-panel .step-desc { color: #64748b; font-size: 0.9rem; margin-bottom: 25px; }
        .form-label { font-weight: 500; color: #374151; font-size: 0.9rem; margin-bottom: 6px; }
        .form-control, .form-select { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; font-size: 0.95rem; transition: all 0.2s ease; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .form-control.is-invalid { border-color: #ef4444; }
        .invalid-feedback { font-size: 0.82rem; color: #ef4444; }
        .input-group-text { background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; color: #64748b; }
        .form-text { font-size: 0.8rem; color: #94a3b8; }
        .logo-upload { border: 2px dashed #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s ease; background: #f8fafc; }
        .logo-upload:hover { border-color: var(--primary); background: #eef2ff; }
        .logo-upload i { font-size: 2rem; color: #94a3b8; margin-bottom: 8px; }
        .logo-upload p { margin: 0; color: #64748b; font-size: 0.9rem; }
        .logo-preview { max-width: 120px; max-height: 80px; margin-top: 10px; display: none; border-radius: 8px; }
        .form-actions { display: flex; justify-content: space-between; padding: 20px 35px; background: #f8fafc; border-top: 1px solid #e2e8f0; }
        .btn-install { background: var(--primary); color: white; border: none; border-radius: 10px; padding: 10px 28px; font-weight: 600; font-size: 0.95rem; transition: all 0.2s ease; }
        .btn-install:hover { background: var(--primary-dark); color: white; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
        .btn-install:disabled { opacity: 0.6; transform: none; box-shadow: none; }
        .btn-secondary-custom { background: white; color: #64748b; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 28px; font-weight: 500; font-size: 0.95rem; transition: all 0.2s ease; }
        .btn-secondary-custom:hover { background: #f8fafc; color: #374151; border-color: #cbd5e1; }
        .btn-test { background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0; border-radius: 10px; padding: 8px 20px; font-weight: 500; font-size: 0.85rem; transition: all 0.2s ease; }
        .btn-test:hover { background: #dcfce7; color: #15803d; }
        .alert-custom { border-radius: 10px; font-size: 0.9rem; border: none; }
        .summary-section { background: #f8fafc; border-radius: 12px; padding: 18px; margin-bottom: 15px; }
        .summary-section h6 { font-weight: 600; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .summary-section h6 i { color: var(--primary); }
        .summary-item { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.9rem; border-bottom: 1px solid #e2e8f0; }
        .summary-item:last-child { border-bottom: none; }
        .summary-item .label { color: #64748b; }
        .summary-item .value { color: #1e293b; font-weight: 500; }
        .loading-overlay { display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); z-index: 100; border-radius: 16px; align-items: center; justify-content: center; flex-direction: column; }
        .loading-overlay.show { display: flex; }
        .spinner-grow-custom { width: 3rem; height: 3rem; color: var(--primary); }
        .password-toggle { cursor: pointer; color: #94a3b8; transition: color 0.2s; }
        .password-toggle:hover { color: var(--primary); }
        .success-container { text-align: center; padding: 60px 30px; }
        .success-container i { font-size: 4rem; color: var(--success); margin-bottom: 20px; }
        .success-container h3 { color: #1e293b; font-weight: 700; }
        .success-container p { color: #64748b; font-size: 1.05rem; }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1><i class="fas fa-clock me-2"></i>CheckTime</h1>
            <p>Assistant d'installation</p>
        </div>
        <div class="installer-card position-relative" id="installerCard">
            <div class="progress-steps" id="progressSteps">
                <div class="step-indicator active" data-step="1"><div class="step-circle">1</div><span class="step-label">Application</span></div>
                <div class="step-indicator" data-step="2"><div class="step-circle">2</div><span class="step-label">Admin</span></div>
                <div class="step-indicator" data-step="3"><div class="step-circle">3</div><span class="step-label">API</span></div>
                <div class="step-indicator" data-step="4"><div class="step-circle">4</div><span class="step-label">SMTP</span></div>
                <div class="step-indicator" data-step="5"><div class="step-circle">5</div><span class="step-label">Installer</span></div>
            </div>
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner-grow spinner-grow-custom" role="status"></div>
                <p class="mt-3 fw-semibold text-muted" id="loadingText">Chargement...</p>
            </div>

            <!-- Step 1: Application Info -->
            <div class="step-panel active" id="step1">
                <div class="form-content">
                    <h3><i class="fas fa-cog me-2 text-primary"></i>Informations de l'application</h3>
                    <p class="step-desc">Configurez les paramètres généraux de votre application.</p>
                    <div id="step1Alert"></div>
                    <form id="formStep1" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nom de l'application <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="app_name" id="app_name" placeholder="Ex: CheckTime" value="CheckTime" required>
                                <div class="invalid-feedback" id="error_app_name"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Logo</label>
                                <div class="logo-upload" onclick="document.getElementById('app_logo').click()">
                                    <i class="fas fa-cloud-upload-alt d-block"></i>
                                    <p>Cliquez pour choisir</p>
                                    <img class="logo-preview" id="logoPreview">
                                    <input type="file" name="app_logo" id="app_logo" accept="image/png,image/jpeg,image/svg+xml" style="display:none">
                                </div>
                                <div class="invalid-feedback" id="error_app_logo"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fuseau horaire <span class="text-danger">*</span></label>
                                <select class="form-select" name="timezone" id="timezone" required>
                                    <option value="UTC">UTC</option>
                                    <option value="Europe/Paris" selected>Europe/Paris</option>
                                    <option value="Europe/London">Europe/London</option>
                                    <option value="Africa/Casablanca">Africa/Casablanca</option>
                                    <option value="Africa/Algiers">Africa/Algiers</option>
                                    <option value="Africa/Tunis">Africa/Tunis</option>
                                    <option value="America/New_York">America/New_York</option>
                                    <option value="America/Chicago">America/Chicago</option>
                                    <option value="Asia/Dubai">Asia/Dubai</option>
                                    <option value="Asia/Tokyo">Asia/Tokyo</option>
                                </select>
                                <div class="invalid-feedback" id="error_timezone"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Langue <span class="text-danger">*</span></label>
                                <select class="form-select" name="locale" id="locale" required>
                                    <option value="fr" selected>Français</option>
                                    <option value="en">English</option>
                                    <option value="ar">العربية</option>
                                </select>
                                <div class="invalid-feedback" id="error_locale"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="form-actions">
                    <div></div>
                    <button type="button" class="btn btn-install" onclick="submitStep(1)">Suivant <i class="fas fa-arrow-right ms-2"></i></button>
                </div>
            </div>

            <!-- Step 2: Administrator -->
            <div class="step-panel" id="step2">
                <div class="form-content">
                    <h3><i class="fas fa-user-shield me-2 text-primary"></i>Compte administrateur</h3>
                    <p class="step-desc">Créez le compte administrateur principal de l'application.</p>
                    <div id="step2Alert"></div>
                    <form id="formStep2">
                        <div class="mb-3">
                            <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="full_name" id="full_name" placeholder="Ex: Jean Dupont" required>
                            </div>
                            <div class="invalid-feedback" id="error_full_name"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="email" placeholder="admin@example.com" required>
                            </div>
                            <div class="invalid-feedback" id="error_email"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Min. 8 caractères" required>
                                    <span class="input-group-text password-toggle" onclick="togglePassword('password')"><i class="fas fa-eye"></i></span>
                                </div>
                                <div class="invalid-feedback" id="error_password"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmer <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" placeholder="Confirmez le mot de passe" required>
                                    <span class="input-group-text password-toggle" onclick="togglePassword('password_confirmation')"><i class="fas fa-eye"></i></span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary-custom" onclick="goToStep(1)"><i class="fas fa-arrow-left me-2"></i>Précédent</button>
                    <button type="button" class="btn btn-install" onclick="submitStep(2)">Suivant <i class="fas fa-arrow-right ms-2"></i></button>
                </div>
            </div>

            <!-- Step 3: Endpoint Configuration -->
            <div class="step-panel" id="step3">
                <div class="form-content">
                    <h3><i class="fas fa-server me-2 text-primary"></i>Configuration de l'endpoint API</h3>
                    <p class="step-desc">Configurez la connexion à l'API CheckTime pour la synchronisation des pointages.</p>
                    <div id="step3Alert"></div>
                    <form id="formStep3">
                        <div class="mb-3">
                            <label class="form-label">URL de l'API <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-link"></i></span>
                                <input type="url" class="form-control" name="api_url" id="api_url" placeholder="https://api.example.com" required>
                            </div>
                            <div class="form-text">L'URL de base de l'API CheckTime (sans le slash final)</div>
                            <div class="invalid-feedback" id="error_api_url"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Token API (General Token) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control" name="api_token" id="api_token" placeholder="Entrez votre token API général" required>
                                <span class="input-group-text password-toggle" onclick="togglePassword('api_token')"><i class="fas fa-eye"></i></span>
                            </div>
                            <div class="form-text">Le token d'authentification général pour accéder à l'API CheckTime.</div>
                            <div class="invalid-feedback" id="error_api_token"></div>
                        </div>
                        <button type="button" class="btn btn-test" onclick="testApiConnection()"><i class="fas fa-plug me-2"></i>Tester la connexion</button>
                        <div id="apiTestResult" class="mt-2"></div>
                    </form>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary-custom" onclick="goToStep(2)"><i class="fas fa-arrow-left me-2"></i>Précédent</button>
                    <button type="button" class="btn btn-install" onclick="submitStep(3)">Suivant <i class="fas fa-arrow-right ms-2"></i></button>
                </div>
            </div>

            <!-- Step 4: SMTP Configuration -->
            <div class="step-panel" id="step4">
                <div class="form-content">
                    <h3><i class="fas fa-envelope me-2 text-primary"></i>Configuration SMTP</h3>
                    <p class="step-desc">Configurez le serveur d'envoi d'emails pour les notifications et rapports. <span class="badge bg-secondary">Optionnel</span></p>
                    <div id="step4Alert"></div>
                    <form id="formStep4">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Serveur SMTP <small class="text-muted">(Optionnel)</small></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-server"></i></span>
                                    <input type="text" class="form-control" name="mail_host" id="mail_host" placeholder="smtp.gmail.com">
                                </div>
                                <div class="invalid-feedback" id="error_mail_host"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Port <small class="text-muted">(Optionnel)</small></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-network-wired"></i></span>
                                    <input type="number" class="form-control" name="mail_port" id="mail_port" value="587">
                                </div>
                                <div class="invalid-feedback" id="error_mail_port"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom d'utilisateur <small class="text-muted">(Optionnel)</small></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="mail_username" id="mail_username" placeholder="user@example.com">
                                </div>
                                <div class="invalid-feedback" id="error_mail_username"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mot de passe <small class="text-muted">(Optionnel)</small></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="mail_password" id="mail_password" placeholder="Mot de passe SMTP">
                                    <span class="input-group-text password-toggle" onclick="togglePassword('mail_password')"><i class="fas fa-eye"></i></span>
                                </div>
                                <div class="invalid-feedback" id="error_mail_password"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Chiffrement</label>
                                <select class="form-select" name="mail_encryption" id="mail_encryption">
                                    <option value="tls" selected>TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="">Aucun</option>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Email expéditeur <small class="text-muted">(Optionnel)</small></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="email" class="form-control" name="mail_from_address" id="mail_from_address" placeholder="noreply@example.com">
                                </div>
                                <div class="invalid-feedback" id="error_mail_from_address"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom de l'expéditeur <small class="text-muted">(Optionnel)</small></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" name="mail_from_name" id="mail_from_name" placeholder="CheckTime">
                            </div>
                            <div class="invalid-feedback" id="error_mail_from_name"></div>
                        </div>
                        <button type="button" class="btn btn-test" onclick="testSmtpConnection()"><i class="fas fa-paper-plane me-2"></i>Tester la connexion SMTP</button>
                        <div id="smtpTestResult" class="mt-2"></div>
                    </form>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary-custom" onclick="goToStep(3)"><i class="fas fa-arrow-left me-2"></i>Précédent</button>
                    <button type="button" class="btn btn-install" onclick="submitStep(4)">Suivant <i class="fas fa-arrow-right ms-2"></i></button>
                </div>
            </div>

            <!-- Step 5: Summary & Install -->
            <div class="step-panel" id="step5">
                <div class="form-content">
                    <h3><i class="fas fa-clipboard-check me-2 text-primary"></i>Résumé de l'installation</h3>
                    <p class="step-desc">Vérifiez les informations ci-dessous avant de lancer l'installation.</p>
                    <div id="step5Alert"></div>
                    <div id="summaryContent"></div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary-custom" onclick="goToStep(4)"><i class="fas fa-arrow-left me-2"></i>Précédent</button>
                    <button type="button" class="btn btn-install" id="btnInstall" onclick="runInstallation()" style="background: var(--success);"><i class="fas fa-rocket me-2"></i>Lancer l'installation</button>
                </div>
            </div>

            <!-- Success State -->
            <div class="step-panel" id="stepSuccess">
                <div class="success-container">
                    <i class="fas fa-check-circle"></i>
                    <h3>Installation terminée !</h3>
                    <p>Votre application CheckTime est prête à être utilisée.</p>
                    <p class="mb-4">Vous allez être redirigé vers la page de connexion...</p>
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.closest('.input-group').querySelector('.password-toggle i');
            if (field.type === 'password') { field.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
            else { field.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
        }

        document.getElementById('app_logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) { const p = document.getElementById('logoPreview'); p.src = e.target.result; p.style.display = 'block'; };
                reader.readAsDataURL(file);
            }
        });

        function showLoading(text = 'Chargement...') { document.getElementById('loadingText').textContent = text; document.getElementById('loadingOverlay').classList.add('show'); }
        function hideLoading() { document.getElementById('loadingOverlay').classList.remove('show'); }
        function clearErrors() { document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid')); document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = ''); }

        function showErrors(errors) {
            for (const [field, messages] of Object.entries(errors)) {
                const input = document.getElementById(field);
                const errorDiv = document.getElementById('error_' + field);
                if (input) input.classList.add('is-invalid');
                if (errorDiv) errorDiv.textContent = Array.isArray(messages) ? messages[0] : messages;
            }
        }

        function showAlert(containerId, message, type = 'danger') {
            document.getElementById(containerId).innerHTML = '<div class="alert alert-' + type + ' alert-custom alert-dismissible fade show"><i class="fas fa-' + (type === 'danger' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle') + ' me-2"></i>' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }

        function goToStep(step) {
            document.querySelectorAll('.step-indicator').forEach(el => { const s = parseInt(el.dataset.step); el.classList.remove('active', 'completed'); if (s < step) el.classList.add('completed'); if (s === step) el.classList.add('active'); });
            document.querySelectorAll('.step-panel').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            currentStep = step;
        }

        async function submitStep(step) {
            clearErrors();
            const urls = { 1: '{{ route("installer.app-info") }}', 2: '{{ route("installer.admin") }}', 3: '{{ route("installer.endpoint") }}', 4: '{{ route("installer.smtp") }}' };
            const formData = new FormData(document.getElementById('formStep' + step));
            showLoading(step === 3 ? 'Test de la connexion API...' : 'Enregistrement...');
            try {
                const response = await fetch(urls[step], { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    hideLoading();
                    if (data.success) { if (step === 4) await loadSummary(); goToStep(step + 1); }
                    else { if (data.errors) showErrors(data.errors); if (data.message) showAlert('step' + step + 'Alert', data.message); }
                } else {
                    const text = await response.text();
                    hideLoading();
                    console.error('Server returned non-JSON:', response.status, text);
                    showAlert('step' + step + 'Alert', 'Erreur serveur (HTTP ' + response.status + '). Vérifiez les logs Laravel.');
                }
            } catch (error) { hideLoading(); console.error('Fetch error:', error); showAlert('step' + step + 'Alert', 'Erreur de communication: ' + error.message); }
        }

        async function loadSummary() {
            try {
                const response = await fetch('{{ route("installer.summary") }}', { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) {
                    const d = data.data;
                    let html = '<div class="summary-section"><h6><i class="fas fa-cog"></i> Application</h6>';
                    html += '<div class="summary-item"><span class="label">Nom</span><span class="value">' + (d.app_info?.app_name || '-') + '</span></div>';
                    html += '<div class="summary-item"><span class="label">Fuseau horaire</span><span class="value">' + (d.app_info?.timezone || '-') + '</span></div>';
                    html += '<div class="summary-item"><span class="label">Langue</span><span class="value">' + (d.app_info?.locale || '-') + '</span></div></div>';
                    html += '<div class="summary-section"><h6><i class="fas fa-user-shield"></i> Administrateur</h6>';
                    html += '<div class="summary-item"><span class="label">Nom</span><span class="value">' + (d.admin?.full_name || '-') + '</span></div>';
                    html += '<div class="summary-item"><span class="label">Email</span><span class="value">' + (d.admin?.email || '-') + '</span></div>';
                    html += '<div class="summary-item"><span class="label">Mot de passe</span><span class="value">' + (d.admin?.password || '-') + '</span></div></div>';
                    html += '<div class="summary-section"><h6><i class="fas fa-server"></i> Endpoint API</h6>';
                    html += '<div class="summary-item"><span class="label">URL</span><span class="value">' + (d.endpoint?.api_url || '-') + '</span></div>';
                    html += '<div class="summary-item"><span class="label">Token</span><span class="value">' + (d.endpoint?.api_token || '-') + '</span></div></div>';
                    html += '<div class="summary-section"><h6><i class="fas fa-envelope"></i> SMTP</h6>';
                    html += '<div class="summary-item"><span class="label">Serveur</span><span class="value">' + (d.smtp?.mail_host || '-') + ':' + (d.smtp?.mail_port || '-') + '</span></div>';
                    html += '<div class="summary-item"><span class="label">Utilisateur</span><span class="value">' + (d.smtp?.mail_username || '-') + '</span></div>';
                    html += '<div class="summary-item"><span class="label">Chiffrement</span><span class="value">' + (d.smtp?.mail_encryption || 'Aucun') + '</span></div>';
                    html += '<div class="summary-item"><span class="label">Expéditeur</span><span class="value">' + (d.smtp?.mail_from_name || '-') + ' <' + (d.smtp?.mail_from_address || '-') + '></span></div></div>';
                    document.getElementById('summaryContent').innerHTML = html;
                }
            } catch (error) { showAlert('step5Alert', 'Erreur lors du chargement du résumé.'); }
        }

        async function testApiConnection() {
            clearErrors();
            if (!document.getElementById('api_url').value || !document.getElementById('api_token').value) {
                document.getElementById('apiTestResult').innerHTML = '<div class="alert alert-warning alert-custom"><i class="fas fa-exclamation-triangle me-2"></i>Veuillez remplir tous les champs.</div>'; return;
            }
            document.getElementById('apiTestResult').innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Test en cours...</div>';
            try {
                const response = await fetch('{{ route("installer.endpoint") }}', { method: 'POST', body: new FormData(document.getElementById('formStep3')), headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('apiTestResult').innerHTML = '<div class="alert alert-success alert-custom"><i class="fas fa-check-circle me-2"></i>Connexion API réussie !</div>';
                } else {
                    document.getElementById('apiTestResult').innerHTML = '<div class="alert alert-danger alert-custom"><i class="fas fa-times-circle me-2"></i>' + (data.message || 'Connexion échouée.') + '</div>';
                }
            } catch (error) { document.getElementById('apiTestResult').innerHTML = '<div class="alert alert-danger alert-custom"><i class="fas fa-times-circle me-2"></i>Erreur de communication.</div>'; }
        }

        async function testSmtpConnection() {
            document.getElementById('smtpTestResult').innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Test en cours...</div>';
            try {
                const response = await fetch('{{ route("installer.test-smtp") }}', { method: 'POST', body: new FormData(document.getElementById('formStep4')), headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) document.getElementById('smtpTestResult').innerHTML = '<div class="alert alert-success alert-custom"><i class="fas fa-check-circle me-2"></i>' + (data.message || 'Email de test envoyé avec succès !') + '</div>';
                else document.getElementById('smtpTestResult').innerHTML = '<div class="alert alert-danger alert-custom"><i class="fas fa-times-circle me-2"></i>' + (data.message || 'Envoi de l\'email de test échoué.') + '</div>';
            } catch (error) { document.getElementById('smtpTestResult').innerHTML = '<div class="alert alert-danger alert-custom"><i class="fas fa-times-circle me-2"></i>Erreur de communication.</div>'; }
        }

        async function runInstallation() {
            const btn = document.getElementById('btnInstall');
            btn.disabled = true;
            showLoading('Installation en cours... Veuillez patienter.');
            try {
                const response = await fetch('{{ route("installer.install") }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' } });
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    hideLoading();
                    if (data.success) {
                        document.getElementById('progressSteps').style.display = 'none';
                        document.querySelectorAll('.step-panel').forEach(el => el.classList.remove('active'));
                        document.getElementById('stepSuccess').classList.add('active');
                        setTimeout(() => { window.location.href = data.redirect || '/login'; }, 3000);
                    } else { showAlert('step5Alert', data.message || 'Erreur lors de l\'installation.'); btn.disabled = false; }
                } else {
                    const text = await response.text();
                    hideLoading();
                    console.error('Server returned non-JSON:', response.status, text);
                    showAlert('step5Alert', 'Erreur serveur (HTTP ' + response.status + '). Vérifiez les logs Laravel.<br><small style="font-size:0.75rem;color:#94a3b8;">Ouvrez la console du navigateur (F12) pour voir les détails.</small>');
                    btn.disabled = false;
                }
            } catch (error) { hideLoading(); console.error('Install error:', error); showAlert('step5Alert', 'Erreur de communication: ' + error.message); btn.disabled = false; }
        }
    </script>
</body>
</html>