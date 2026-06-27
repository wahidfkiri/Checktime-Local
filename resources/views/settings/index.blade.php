@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Paramètres Système</h3>
                        <p class="text-subtitle text-muted">Configuration des emails et notifications</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active">Paramètres</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <!-- Alert de chargement -->
                <div class="alert alert-info alert-dismissible fade show d-none" id="loading-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <strong>Traitement en cours...</strong> Veuillez patienter.
                    </div>
                </div>
                
                <!-- Alert de succès -->
                <div class="alert alert-success alert-dismissible fade show d-none" id="success-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div>
                            <strong id="success-title">Succès</strong>
                            <p class="mb-0" id="success-message"></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                
                <!-- Alert d'erreur -->
                <div class="alert alert-danger alert-dismissible fade show d-none" id="error-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>
                            <strong id="error-title">Erreur</strong>
                            <p class="mb-0" id="error-message"></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <div class="row">
                    <!-- Section Emails RH -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">📧 Configuration Emails RH</h4>
                                <p class="card-subtitle">Rapports mensuels envoyés aux Ressources Humaines</p>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <label for="rh_email" class="form-label">Email RH</label>
                                    <input type="email" class="form-control" id="rh_email" 
                                           placeholder="rh@entreprise.com" value="{{ $settings->email ?? '' }}">
                                    <div class="form-text">Adresse email qui recevra les rapports mensuels</div>
                                </div>
                                
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="email_is_active" 
                                           {{ ($settings->email_is_active ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="email_is_active">
                                        <strong>Activer les emails RH</strong>
                                    </label>
                                    <div class="form-text">
                                        Si activé, les rapports mensuels seront envoyés le dernier jour du mois à 9h
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section Emails Employés -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">👥 Emails aux Employés</h4>
                                <p class="card-subtitle">Rapports hebdomadaires envoyés aux employés</p>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="email_employees_is_active" 
                                           {{ ($settings->email_employees_is_active ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="email_employees_is_active">
                                        <strong>Activer les emails aux employés</strong>
                                    </label>
                                    <div class="form-text">
                                        Si activé, les rapports hebdomadaires seront envoyés tous les samedis à 9h
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h6 class="alert-heading"><i class="bi bi-info-circle me-1"></i> Information</h6>
                                    <p class="mb-0 small">
                                        Les emails sont envoyés aux adresses configurées dans les fiches employés.
                                        Assurez-vous que les employés ont une adresse email valide.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistiques -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">📊 Statistiques</h4>
                            </div>
                            <div class="card-body">
                                <div class="row text-center" id="stats-container">
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <h3 class="mb-0" id="total-employees">0</h3>
                                            <small class="text-muted">Total employés</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <h3 class="mb-0 text-success" id="employees-with-email">0</h3>
                                            <small class="text-muted">Employés avec email</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <h3 class="mb-0" id="rh-status">
                                                <span class="badge bg-danger">Désactivé</span>
                                            </h3>
                                            <small class="text-muted">Statut emails RH</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <h3 class="mb-0" id="employees-email-status">
                                                <span class="badge bg-danger">Désactivé</span>
                                            </h3>
                                            <small class="text-muted">Statut emails employés</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section SMS (optionnelle) -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">📱 Configuration SMS (Optionnel)</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="sms_is_active" 
                                                   {{ ($settings->sms_is_active ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sms_is_active">
                                                <strong>Activer les SMS</strong>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="sms_credit" class="form-label">Crédit SMS</label>
                                            <span class="badge bg-info">{{ $settings->sms_credit ?? 0 }} SMS</span>
                                            <div class="form-text">Nombre de SMS disponibles</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Boutons d'action -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <button type="button" class="btn btn-primary btn-lg me-2" id="save-settings">
                                    <i class="bi bi-save me-1"></i> Enregistrer les paramètres
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Information CRON -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">⏰ Planification des Emails</h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-light">
                                    <h6><i class="bi bi-calendar-week me-1"></i> Planification automatique</h6>
                                    <ul class="mb-0">
                                        <li><strong>Emails RH :</strong> Dernier jour du mois à 9h (rapport mensuel)</li>
                                        <li><strong>Emails employés :</strong> Tous les samedis à 9h (rapport hebdomadaire)</li>
                                        <li><strong>Statut CRON :</strong> <span id="cron-status" class="badge bg-success">Actif</span></li>
                                    </ul>
                                    <div class="mt-2 small text-muted">
                                        <i class="bi bi-info-circle me-1"></i> 
                                        Ces plannings sont gérés automatiquement par le système.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Variables
    var originalSettings = {};
    var isSaving = false;
    var isTesting = false;
    
    // Initialiser les valeurs originales
    loadOriginalSettings();
    
    // Fonctions utilitaires
    function showLoading(message = 'Traitement en cours...') {
        $('#loading-alert strong').text(message);
        $('#loading-alert').removeClass('d-none');
        disableButtons(true);
    }
    
    function hideLoading() {
        $('#loading-alert').addClass('d-none');
        disableButtons(false);
    }
    
    function disableButtons(disabled) {
        if (disabled) {
            isSaving = true;
            $('#save-settings, #reset-settings, #test-rh-email, #test-employees-email').prop('disabled', true);
        } else {
            isSaving = false;
            $('#save-settings, #reset-settings, #test-rh-email, #test-employees-email').prop('disabled', false);
        }
    }
    
    function showSuccessAlert(title, message) {
        $('#success-title').text(title);
        $('#success-message').text(message);
        $('#success-alert').removeClass('d-none');
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('#success-alert').addClass('d-none');
        }, 5000);
    }
    
    function showErrorAlert(title, message) {
        $('#error-title').text(title);
        $('#error-message').text(message);
        $('#error-alert').removeClass('d-none');
    }
    
    function hideAlerts() {
        $('#success-alert, #error-alert').addClass('d-none');
    }
    
    function showSweetAlert(icon, title, text) {
        Swal.fire({
            icon: icon,
            title: title,
            html: text,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    function loadOriginalSettings() {
        originalSettings = {
            email: $('#rh_email').val(),
            email_is_active: $('#email_is_active').prop('checked'),
            email_employees_is_active: $('#email_employees_is_active').prop('checked'),
            sms_is_active: $('#sms_is_active').prop('checked'),
            sms_credit: $('#sms_credit').val()
        };
    }
    
    function updateStats() {
        $.ajax({
            url: "{{ route('settings.status') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var settings = response.settings;
                    var stats = response.stats;
                    
                    // Mettre à jour les statistiques
                    $('#total-employees').text(stats.total_employees);
                    $('#employees-with-email').text(stats.employees_with_email);
                    
                    // Mettre à jour les statuts
                    var rhStatus = settings.email_is_active ? 
                        '<span class="badge bg-success">Activé</span>' : 
                        '<span class="badge bg-danger">Désactivé</span>';
                    $('#rh-status').html(rhStatus);
                    
                    var empStatus = settings.email_employees_is_active ? 
                        '<span class="badge bg-success">Activé</span>' : 
                        '<span class="badge bg-danger">Désactivé</span>';
                    $('#employees-email-status').html(empStatus);
                    
                    // Mettre à jour les champs
                    if (!$('#rh_email').is(':focus')) {
                        $('#rh_email').val(settings.email || '');
                    }
                    $('#email_is_active').prop('checked', settings.email_is_active || false);
                    $('#email_employees_is_active').prop('checked', settings.email_employees_is_active || false);
                    $('#sms_is_active').prop('checked', settings.sms_is_active || false);
                    if (!$('#sms_credit').is(':focus')) {
                        $('#sms_credit').val(settings.sms_credit || 0);
                    }
                    
                    // Mettre à jour les valeurs originales
                    loadOriginalSettings();
                }
            },
            error: function(xhr) {
                console.error('Erreur chargement statistiques:', xhr);
            }
        });
    }
    
    // Charger les statistiques initiales
    updateStats();
    
    // Événements
    
    // Enregistrer les paramètres
    $('#save-settings').on('click', function() {
        if (isSaving) return;
        
        hideAlerts();
        showLoading('Enregistrement des paramètres...');
        
        var settingsData = {
            email: $('#rh_email').val(),
            email_is_active: $('#email_is_active').prop('checked'),
            email_employees_is_active: $('#email_employees_is_active').prop('checked'),
            sms_is_active: $('#sms_is_active').prop('checked'),
            sms_credit: $('#sms_credit').val() || 0
        };
        
        $.ajax({
            url: "{{ route('settings.update') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ...settingsData
            },
            success: function(response) {
                if (response.success) {
                    showSuccessAlert('Succès', response.message);
                    
                    // Mettre à jour les statistiques
                    updateStats();
                    
                    // Afficher un sweet alert
                    Swal.fire({
                        icon: 'success',
                        title: 'Paramètres enregistrés',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    if (response.errors) {
                        var errorMessages = [];
                        $.each(response.errors, function(key, value) {
                            errorMessages.push(value[0]);
                        });
                        showErrorAlert('Erreur de validation', errorMessages.join('<br>'));
                    } else {
                        showErrorAlert('Erreur', response.message);
                    }
                }
            },
            error: function(xhr) {
                var errorMsg = 'Erreur lors de l\'enregistrement';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showErrorAlert('Erreur', errorMsg);
            },
            complete: function() {
                hideLoading();
            }
        });
    });
    
    // Tester l'email RH
    $('#test-rh-email').on('click', function() {
        if (isSaving) return;
        
        var email = $('#rh_email').val();
        if (!email) {
            showErrorAlert('Email requis', 'Veuillez d\'abord configurer un email RH');
            return;
        }
        
        if (isTesting) return;
        isTesting = true;
        
        hideAlerts();
        showLoading('Envoi de l\'email de test...');
        
        $.ajax({
            url: "{{ route('settings.test.rh') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    showSuccessAlert('Test réussi', response.message);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Email envoyé !',
                        html: response.message + '<br><br><small class="text-muted">Vérifiez votre boîte de réception (et les spams)</small>',
                        timer: 4000,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    });
                } else {
                    showErrorAlert('Échec du test', response.message);
                }
            },
            error: function(xhr) {
                var errorMsg = 'Erreur lors de l\'envoi du test';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showErrorAlert('Erreur', errorMsg);
            },
            complete: function() {
                hideLoading();
                isTesting = false;
            }
        });
    });
    
    // Tester l'email employé
    $('#test-employees-email').on('click', function() {
        if (isSaving) return;
        
        if (!$('#email_employees_is_active').prop('checked')) {
            showErrorAlert('Service désactivé', 'Veuillez d\'abord activer les emails aux employés');
            return;
        }
        
        if (isTesting) return;
        isTesting = true;
        
        hideAlerts();
        showLoading('Envoi de l\'email de test...');
        
        $.ajax({
            url: "{{ route('settings.test.employees') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    showSuccessAlert('Test réussi', response.message);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Email envoyé !',
                        html: response.message + '<br><br><small class="text-muted">Vérifiez la boîte de réception de l\'employé</small>',
                        timer: 4000,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    });
                } else {
                    showErrorAlert('Échec du test', response.message);
                }
            },
            error: function(xhr) {
                var errorMsg = 'Erreur lors de l\'envoi du test';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showErrorAlert('Erreur', errorMsg);
            },
            complete: function() {
                hideLoading();
                isTesting = false;
            }
        });
    });
    
    // Rétablir les valeurs
    $('#reset-settings').on('click', function() {
        Swal.fire({
            title: 'Rétablir les valeurs ?',
            text: 'Tous les changements non enregistrés seront perdus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, rétablir',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#rh_email').val(originalSettings.email);
                $('#email_is_active').prop('checked', originalSettings.email_is_active);
                $('#email_employees_is_active').prop('checked', originalSettings.email_employees_is_active);
                $('#sms_is_active').prop('checked', originalSettings.sms_is_active);
                $('#sms_credit').val(originalSettings.sms_credit);
                
                showSweetAlert('success', 'Valeurs rétablies', 'Les valeurs originales ont été rétablies.');
            }
        });
    });
    
    // Rafraîchir les statistiques
    $('#refresh-stats').on('click', function() {
        updateStats();
        showSweetAlert('info', 'Statistiques mises à jour', 'Les statistiques ont été rafraîchies.');
    });
    
    // Fermer les alerts
    $('.alert .btn-close').on('click', function() {
        $(this).closest('.alert').addClass('d-none');
    });
    
    // Auto-save sur changement d'email (optionnel)
    var emailTimer;
    $('#rh_email').on('keyup', function() {
        clearTimeout(emailTimer);
        emailTimer = setTimeout(function() {
            if (!$('#rh_email').val()) {
                $('#email_is_active').prop('checked', false);
            }
        }, 1000);
    });
    
    // Initialiser le statut CRON
    function checkCronStatus() {
        // Cette fonction pourrait vérifier l'état des crons via une API
        // Pour l'instant, on suppose qu'ils sont actifs
        $('#cron-status').removeClass('bg-danger bg-warning').addClass('bg-success').text('Actif');
    }
    
    checkCronStatus();
});
</script>

<style>
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .form-check-input:checked {
        background-color: #4CAF50;
        border-color: #4CAF50;
    }
    
    .form-check-input:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    #stats-container .border {
        transition: all 0.3s ease;
        border: 1px solid #dee2e6 !important;
    }
    
    #stats-container .border:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    #stats-container h3 {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .badge {
        font-size: 0.9em;
        padding: 0.5em 1em;
        font-weight: 600;
    }
    
    .btn-lg {
        padding: 0.75rem 2rem;
        font-size: 1.1rem;
    }
    
    .alert {
        border-left: 4px solid;
    }
    
    .alert-success {
        border-left-color: #198754;
    }
    
    .alert-danger {
        border-left-color: #dc3545;
    }
    
    .alert-info {
        border-left-color: #0dcaf0;
    }
    
    .alert-light {
        border-left-color: #6c757d;
    }
    
    /* Animation pour les badges de statut */
    .badge {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .btn-lg {
            width: 100%;
            margin-bottom: 10px;
        }
        
        #stats-container .col-md-3 {
            margin-bottom: 15px;
        }
        
        #stats-container h3 {
            font-size: 1.5rem;
        }
        
        .card-header h4 {
            font-size: 1.2rem;
        }
    }
    
    @media (max-width: 576px) {
        .row {
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .alert {
            font-size: 0.9rem;
        }
    }
    
    /* Style pour les switch */
    .form-check-input {
        width: 3em !important;
        height: 1.5em !important;
    }
    
    .form-check-label {
        font-weight: 500;
        color: #495057;
    }
    
    /* Style pour les inputs désactivés */
    input:disabled, button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Style pour le bouton de test */
    #test-rh-email, #test-employees-email {
        transition: all 0.3s ease;
    }
    
    #test-rh-email:hover, #test-employees-email:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
</style>
@endsection