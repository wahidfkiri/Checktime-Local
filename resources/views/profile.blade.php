@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Mon Profil</h3>
                        <p class="text-subtitle text-muted">
                            Gérez vos informations personnelles, votre mot de passe et les notifications
                        </p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Profil</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                {{-- Onglets --}}
                <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-profil-btn" data-bs-toggle="tab"
                                data-bs-target="#tab-profil" type="button" role="tab">
                            <i class="bi bi-person-circle me-1"></i> Mon Profil
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-notif-btn" data-bs-toggle="tab"
                                data-bs-target="#tab-notif" type="button" role="tab">
                            <i class="bi bi-bell me-1"></i> Paramètres de notification
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabsContent">
                    {{-- ================= ONGLET PROFIL ================= --}}
                    <div class="tab-pane fade show active" id="tab-profil" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4">
                                <!-- Carte Profil Utilisateur -->
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Informations du profil</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex flex-column align-items-center text-center">
                                            <div class="mb-3">
                                                <div class="avatar-initials"
                                                     style="width: 100px; height: 100px;
                                                            background-color: #4361ee;
                                                            border-radius: 50%;
                                                            display: flex;
                                                            align-items: center;
                                                            justify-content: center;
                                                            font-size: 40px;
                                                            font-weight: bold;
                                                            color: white;">
                                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <h4>{{ Auth::user()->name }}</h4>
                                                <p class="text-secondary mb-1">{{ Auth::user()->email }}</p>
                                                <p class="text-muted font-size-sm">
                                                    Membre depuis {{ Auth::user()->created_at->format('d/m/Y') }}
                                                </p>
                                            </div>

                                            <div class="mt-2">
                                                <span class="badge bg-success">Actif</span>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <div class="row">
                                            <div class="col-12">
                                                <h6 class="mb-3">Détails du compte</h6>
                                                <div class="mb-2">
                                                    <p class="text-muted mb-0">Nom complet</p>
                                                    <p class="fw-bold">{{ Auth::user()->name }}</p>
                                                </div>
                                                <div class="mb-2">
                                                    <p class="text-muted mb-0">Email</p>
                                                    <p class="fw-bold">{{ Auth::user()->email }}</p>
                                                </div>
                                                <div class="mb-2">
                                                    <p class="text-muted mb-0">Date d'inscription</p>
                                                    <p class="fw-bold">{{ Auth::user()->created_at->format('d/m/Y à H:i') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <!-- Formulaire de modification du profil -->
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Modifier le profil</h4>
                                    </div>
                                    <div class="card-body">
                                        @if(session('success'))
                                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                <i class="bi bi-check-circle me-2"></i>
                                                {{ session('success') }}
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                        @endif

                                        @if($errors->any())
                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                <ul class="mb-0">
                                                    @foreach($errors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                        @endif

                                        <form action="{{ route('profile.update') }}" method="POST" class="mb-4">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="update_type" value="profile">

                                            <h6 class="text-muted mb-3">Informations personnelles</h6>

                                            <div class="row">
                                                <div class="form-group col-md-6">
                                                    <label for="name" class="form-label">Nom complet *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                        <input type="text"
                                                               class="form-control @error('name') is-invalid @enderror"
                                                               id="name" name="name"
                                                               value="{{ old('name', Auth::user()->name) }}" required>
                                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="email" class="form-label">Adresse email *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                                        <input type="email"
                                                               class="form-control @error('email') is-invalid @enderror"
                                                               id="email" name="email"
                                                               value="{{ old('email', Auth::user()->email) }}" required>
                                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-check-circle me-1"></i> Mettre à jour le profil
                                                </button>
                                            </div>
                                        </form>

                                        <hr class="my-4">

                                        <form action="{{ route('profile.update') }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="update_type" value="password">

                                            <h6 class="text-muted mb-3">Changer le mot de passe</h6>

                                            <div class="row">
                                                <div class="form-group col-md-6">
                                                    <label for="current_password" class="form-label">Mot de passe actuel *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                                        <input type="password"
                                                               class="form-control @error('current_password') is-invalid @enderror"
                                                               id="current_password" name="current_password" required>
                                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="password" class="form-label">Nouveau mot de passe *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                                        <input type="password"
                                                               class="form-control @error('password') is-invalid @enderror"
                                                               id="password" name="password" required>
                                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="form-text">Minimum 8 caractères</div>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="form-group col-md-6">
                                                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                                                        <input type="password" class="form-control"
                                                               id="password_confirmation" name="password_confirmation" required>
                                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label class="form-label d-block">Force du mot de passe</label>
                                                    <div class="password-strength-meter">
                                                        <div class="progress" style="height: 8px;">
                                                            <div id="password-strength" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                        </div>
                                                        <small id="password-strength-text" class="text-muted"></small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="bi bi-shield-lock me-1"></i> Changer le mot de passe
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ================= ONGLET NOTIFICATIONS ================= --}}
                    <div class="tab-pane fade" id="tab-notif" role="tabpanel">

                        {{-- Zone d'alerte notifications --}}
                        <div id="notif-alert" class="alert d-none" role="alert"></div>

                        {{-- Carte SMTP --}}
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0"><i class="bi bi-envelope-gear me-1"></i> Configuration SMTP (envoi des emails)</h4>
                                @if(!empty($mail['mail_host']))
                                    <span class="badge bg-success">Configuré</span>
                                @else
                                    <span class="badge bg-secondary">Non configuré</span>
                                @endif
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    Ces paramètres servent à l'envoi des rapports par email. Renseignez-les si les champs sont vides,
                                    puis testez l'envoi.
                                </p>
                                <form id="smtp-form">
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label class="form-label">Serveur SMTP (host) *</label>
                                            <input type="text" class="form-control" name="mail_host"
                                                   value="{{ $mail['mail_host'] ?? '' }}" placeholder="smtp.gmail.com" required>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label class="form-label">Port *</label>
                                            <input type="number" class="form-control" name="mail_port"
                                                   value="{{ $mail['mail_port'] ?? 587 }}" placeholder="587" required>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label class="form-label">Chiffrement</label>
                                            <select class="form-select" name="mail_encryption">
                                                <option value="" {{ empty($mail['mail_encryption']) ? 'selected' : '' }}>Aucun</option>
                                                <option value="tls" {{ ($mail['mail_encryption'] ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                                                <option value="ssl" {{ ($mail['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="form-group col-md-6">
                                            <label class="form-label">Utilisateur</label>
                                            <input type="text" class="form-control" name="mail_username"
                                                   value="{{ $mail['mail_username'] ?? '' }}" placeholder="user@domaine.com" autocomplete="off">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label class="form-label">Mot de passe</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" name="mail_password"
                                                       placeholder="{{ !empty($mail['mail_password']) ? '•••••••• (laisser vide pour ne pas changer)' : 'Mot de passe SMTP' }}"
                                                       autocomplete="new-password">
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="form-group col-md-6">
                                            <label class="form-label">Expéditeur (email) *</label>
                                            <input type="email" class="form-control" name="mail_from_address"
                                                   value="{{ $mail['mail_from_address'] ?? '' }}" placeholder="no-reply@domaine.com" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label class="form-label">Expéditeur (nom)</label>
                                            <input type="text" class="form-control" name="mail_from_name"
                                                   value="{{ $mail['mail_from_name'] ?? '' }}" placeholder="CheckTime">
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row align-items-end">
                                        <div class="form-group col-md-6">
                                            <label class="form-label">Envoyer un email de test à</label>
                                            <input type="email" class="form-control" id="smtp-test-email"
                                                   value="{{ Auth::user()->email }}" placeholder="destinataire@domaine.com">
                                        </div>
                                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                            <button type="button" class="btn btn-outline-primary" id="btn-test-smtp">
                                                <i class="bi bi-send me-1"></i> Tester l'envoi
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-1"></i> Enregistrer SMTP
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Carte SMS --}}
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0"><i class="bi bi-chat-dots me-1"></i> Configuration SMS (clé API)</h4>
                                @if(!empty($smsApiKey))
                                    <span class="badge bg-success">Configuré</span>
                                @else
                                    <span class="badge bg-secondary">Non configuré</span>
                                @endif
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    Renseignez la clé API du fournisseur SMS (FastWay), puis testez la connexion.
                                </p>
                                <form id="sms-form">
                                    <div class="row align-items-end">
                                        <div class="form-group col-md-8">
                                            <label class="form-label">Clé API SMS *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                                <input type="password" class="form-control" name="sms_api_key"
                                                       value="{{ $smsApiKey }}" placeholder="Votre clé API" autocomplete="off" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                            <button type="button" class="btn btn-outline-primary" id="btn-test-sms">
                                                <i class="bi bi-broadcast me-1"></i> Tester
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-1"></i> Enregistrer
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Carte Tâches planifiées --}}
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4 class="card-title mb-0"><i class="bi bi-clock-history me-1"></i> Tâches planifiées (rapports par email)</h4>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    Activez ou désactivez chaque rapport, définissez sa date/heure d'exécution et les destinataires.
                                    Le planificateur système (cron <code>schedule:run</code>) doit être actif pour l'envoi automatique.
                                </p>

                                <form id="jobs-form">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead>
                                                <tr>
                                                    <th style="min-width:220px">Tâche</th>
                                                    <th class="text-center">Actif</th>
                                                    <th style="min-width:320px">Planification</th>
                                                    <th style="min-width:220px">Destinataires</th>
                                                    <th class="text-center">Dernière exécution</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($scheduledJobs ?? [] as $job)
                                                <tr class="job-row" data-id="{{ $job->id }}" data-command="{{ $job->command }}">
                                                    <td>
                                                        <div class="fw-bold">{{ $job->label }}</div>
                                                        <small class="text-muted">{{ $job->description }}</small><br>
                                                        <code class="small">{{ $job->command }}</code>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input class="form-check-input js-active" type="checkbox"
                                                                   role="switch" {{ $job->is_active ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="row g-1">
                                                            <div class="col-12">
                                                                <select class="form-select form-select-sm js-frequency">
                                                                    <option value="daily"   {{ $job->frequency === 'daily' ? 'selected' : '' }}>Quotidien</option>
                                                                    <option value="weekly"  {{ $job->frequency === 'weekly' ? 'selected' : '' }}>Hebdomadaire</option>
                                                                    <option value="monthly" {{ $job->frequency === 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                                                    <option value="once"    {{ $job->frequency === 'once' ? 'selected' : '' }}>Date précise (une fois)</option>
                                                                    <option value="custom"  {{ $job->frequency === 'custom' ? 'selected' : '' }}>Personnalisé (cron)</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-6 js-field js-field-time">
                                                                <input type="time" class="form-control form-control-sm js-time"
                                                                       value="{{ $job->time ?: '09:00' }}">
                                                            </div>
                                                            <div class="col-6 js-field js-field-dow">
                                                                <select class="form-select form-select-sm js-dow">
                                                                    @foreach([1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi',7=>'Dimanche'] as $k=>$v)
                                                                        <option value="{{ $k }}" {{ (int)$job->day_of_week === $k ? 'selected' : '' }}>{{ $v }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-6 js-field js-field-dom">
                                                                <input type="number" min="1" max="31" class="form-control form-control-sm js-dom"
                                                                       placeholder="Jour" value="{{ $job->day_of_month ?: '' }}">
                                                            </div>
                                                            <div class="col-12 js-field js-field-cron">
                                                                <input type="text" class="form-control form-control-sm js-cron"
                                                                       placeholder="* * * * *" value="{{ $job->cron_expression ?: '' }}">
                                                            </div>
                                                            <div class="col-12 js-field js-field-runat">
                                                                <input type="datetime-local" class="form-control form-control-sm js-runat"
                                                                       value="{{ $job->run_at ? $job->run_at->format('Y-m-d\TH:i') : '' }}">
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($job->supports_recipients)
                                                            <input type="text" class="form-control form-control-sm js-recipients"
                                                                   placeholder="email1@x.com, email2@y.com"
                                                                   value="{{ is_array($job->recipients) ? implode(', ', $job->recipients) : '' }}">
                                                            <small class="text-muted">Séparez par des virgules</small>
                                                        @else
                                                            <span class="text-muted">Envoyé à chaque employé</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <small class="text-muted">
                                                            {{ $job->last_run_at ? $job->last_run_at->format('d/m/Y H:i') : '—' }}
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-success js-run" title="Exécuter maintenant">
                                                            <i class="bi bi-play-fill"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="text-center text-muted">Aucune tâche disponible.</td></tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-3 text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i> Enregistrer les tâches
                                        </button>
                                    </div>
                                </form>

                                {{-- Sortie d'exécution --}}
                                <div id="run-output-wrapper" class="mt-3 d-none">
                                    <label class="form-label fw-bold">Résultat de l'exécution :</label>
                                    <pre id="run-output" class="bg-dark text-light p-3 rounded" style="max-height:300px;overflow:auto;font-size:12px;"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Scripts pour la page profil -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // ====== Afficher/masquer le mot de passe ======
    $(document).on('click', '.toggle-password', function() {
        const input = $(this).closest('.input-group').find('input');
        const icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // ====== Force du mot de passe ======
    $('#password').on('keyup', function() {
        const password = $(this).val();
        const strength = checkPasswordStrength(password);
        updatePasswordStrength(password, strength);
    });

    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[$@#&!%^*()\-_=+[\]{};:'",.<>/?\\|`~]+/)) strength++;
        return strength;
    }

    function updatePasswordStrength(password, strength) {
        const strengthBar = $('#password-strength');
        const strengthText = $('#password-strength-text');
        strengthBar.removeClass().addClass('progress-bar');
        if (password.length === 0) {
            strengthBar.css('width', '0%');
            strengthText.text('');
            return;
        }
        if (strength < 2) {
            strengthBar.addClass('bg-danger').css('width', '20%');
            strengthText.text('Faible');
        } else if (strength < 4) {
            strengthBar.addClass('bg-warning').css('width', '60%');
            strengthText.text('Moyen');
        } else {
            strengthBar.addClass('bg-success').css('width', '100%');
            strengthText.text('Fort');
        }
    }

    // Avatar couleur
    function generateColorFromName(name) {
        const colors = ['#4361ee','#3a56d4','#2ecc71','#27ae60','#e74c3c','#c0392b','#f39c12','#d35400','#9b59b6','#8e44ad','#1abc9c','#16a085','#3498db','#2980b9','#e67e22'];
        let hash = 0;
        for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
        return colors[Math.abs(hash) % colors.length];
    }
    const userName = "{{ Auth::user()->name }}";
    $('.avatar-initials').css('background-color', generateColorFromName(userName));

    // =========================================================
    //  PARAMÈTRES DE NOTIFICATION
    // =========================================================
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const routes = {
        smtp:     "{{ route('profile.notifications.smtp') }}",
        smtpTest: "{{ route('profile.notifications.smtp.test') }}",
        sms:      "{{ route('profile.notifications.sms') }}",
        smsTest:  "{{ route('profile.notifications.sms.test') }}",
        jobs:     "{{ route('profile.notifications.jobs') }}",
        jobsRun:  "{{ route('profile.notifications.jobs.run') }}",
    };

    function notify(type, message) {
        const cls = type === 'success' ? 'alert-success' : 'alert-danger';
        $('#notif-alert').removeClass('d-none alert-success alert-danger')
            .addClass(cls).html(message);
        $('html, body').animate({ scrollTop: $('#notif-alert').offset().top - 100 }, 300);
    }

    function ajaxJson(url, data) {
        return $.ajax({
            url: url, method: 'POST', dataType: 'json',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            data: JSON.stringify(data)
        });
    }

    function smtpPayload() {
        return {
            mail_host:         $('#smtp-form [name=mail_host]').val(),
            mail_port:         $('#smtp-form [name=mail_port]').val(),
            mail_encryption:   $('#smtp-form [name=mail_encryption]').val(),
            mail_username:     $('#smtp-form [name=mail_username]').val(),
            mail_password:     $('#smtp-form [name=mail_password]').val(),
            mail_from_address: $('#smtp-form [name=mail_from_address]').val(),
            mail_from_name:    $('#smtp-form [name=mail_from_name]').val(),
        };
    }

    // ---- Enregistrer SMTP ----
    $('#smtp-form').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true);
        ajaxJson(routes.smtp, smtpPayload())
            .done(r => notify('success', '<i class="bi bi-check-circle me-2"></i>' + r.message))
            .fail(x => notify('error', '<i class="bi bi-x-circle me-2"></i>' + (x.responseJSON?.message || 'Erreur lors de l\'enregistrement.')))
            .always(() => btn.prop('disabled', false));
    });

    // ---- Tester SMTP ----
    $('#btn-test-smtp').on('click', function() {
        const btn = $(this);
        const payload = smtpPayload();
        payload.test_email = $('#smtp-test-email').val();
        if (!payload.test_email) { notify('error', 'Indiquez une adresse email de test.'); return; }
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Envoi...');
        ajaxJson(routes.smtpTest, payload)
            .done(r => notify('success', '<i class="bi bi-check-circle me-2"></i>' + r.message))
            .fail(x => notify('error', '<i class="bi bi-x-circle me-2"></i>' + (x.responseJSON?.message || 'Échec de l\'envoi de test.')))
            .always(() => btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i> Tester l\'envoi'));
    });

    // ---- Enregistrer la clé API SMS ----
    $('#sms-form').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true);
        ajaxJson(routes.sms, { sms_api_key: $('#sms-form [name=sms_api_key]').val() })
            .done(r => notify('success', '<i class="bi bi-check-circle me-2"></i>' + r.message))
            .fail(x => notify('error', '<i class="bi bi-x-circle me-2"></i>' + (x.responseJSON?.message || 'Erreur lors de l\'enregistrement.')))
            .always(() => btn.prop('disabled', false));
    });

    // ---- Tester la clé API SMS ----
    $('#btn-test-sms').on('click', function() {
        const btn = $(this);
        const key = $('#sms-form [name=sms_api_key]').val();
        if (!key) { notify('error', 'Renseignez la clé API SMS.'); return; }
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Test...');
        ajaxJson(routes.smsTest, { sms_api_key: key })
            .done(r => notify('success', '<i class="bi bi-check-circle me-2"></i>' + r.message))
            .fail(x => notify('error', '<i class="bi bi-x-circle me-2"></i>' + (x.responseJSON?.message || 'Échec du test SMS.')))
            .always(() => btn.prop('disabled', false).html('<i class="bi bi-broadcast me-1"></i> Tester'));
    });

    // ---- Affichage conditionnel des champs de planification ----
    function refreshJobFields($row) {
        const freq = $row.find('.js-frequency').val();
        $row.find('.js-field-time').toggle(freq === 'daily' || freq === 'weekly' || freq === 'monthly');
        $row.find('.js-field-dow').toggle(freq === 'weekly');
        $row.find('.js-field-dom').toggle(freq === 'monthly');
        $row.find('.js-field-cron').toggle(freq === 'custom');
        $row.find('.js-field-runat').toggle(freq === 'once');
    }
    $('.job-row').each(function() { refreshJobFields($(this)); });
    $(document).on('change', '.js-frequency', function() { refreshJobFields($(this).closest('.job-row')); });

    function splitEmails(str) {
        return (str || '').split(/[,;\s]+/).map(s => s.trim()).filter(s => s.length > 0);
    }

    // ---- Enregistrer les tâches ----
    $('#jobs-form').on('submit', function(e) {
        e.preventDefault();
        const jobs = [];
        $('.job-row').each(function() {
            const $r = $(this);
            jobs.push({
                id:              parseInt($r.data('id')),
                is_active:       $r.find('.js-active').is(':checked'),
                frequency:       $r.find('.js-frequency').val(),
                time:            $r.find('.js-time').val() || '09:00',
                day_of_week:     $r.find('.js-dow').val() ? parseInt($r.find('.js-dow').val()) : null,
                day_of_month:    $r.find('.js-dom').val() ? parseInt($r.find('.js-dom').val()) : null,
                cron_expression: $r.find('.js-cron').val() || null,
                run_at:          $r.find('.js-runat').val() || null,
                recipients:      $r.find('.js-recipients').length ? splitEmails($r.find('.js-recipients').val()) : []
            });
        });
        const btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true);
        ajaxJson(routes.jobs, { jobs: jobs })
            .done(r => notify('success', '<i class="bi bi-check-circle me-2"></i>' + r.message))
            .fail(x => notify('error', '<i class="bi bi-x-circle me-2"></i>' + (x.responseJSON?.message || 'Erreur lors de l\'enregistrement des tâches.')))
            .always(() => btn.prop('disabled', false));
    });

    // ---- Exécuter une tâche maintenant ----
    $(document).on('click', '.js-run', function() {
        const $r = $(this).closest('.job-row');
        const command = $r.data('command');
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        ajaxJson(routes.jobsRun, { command: command })
            .done(r => {
                notify('success', '<i class="bi bi-check-circle me-2"></i>' + r.message);
                $('#run-output-wrapper').removeClass('d-none');
                $('#run-output').text(r.output || '(aucune sortie)');
            })
            .fail(x => {
                notify('error', '<i class="bi bi-x-circle me-2"></i>' + (x.responseJSON?.message || 'Erreur d\'exécution.'));
                if (x.responseJSON?.output) {
                    $('#run-output-wrapper').removeClass('d-none');
                    $('#run-output').text(x.responseJSON.output);
                }
            })
            .always(() => btn.prop('disabled', false).html('<i class="bi bi-play-fill"></i>'));
    });
});
</script>

<style>
.avatar-initials { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.avatar-initials:hover { transform: scale(1.05); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); }
.card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border: none; }
.card-header { background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; border-top-left-radius: 10px !important; border-top-right-radius: 10px !important; }
.form-control:focus, .form-select:focus { border-color: #4361ee; box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25); }
.input-group-text { background-color: #f8f9fa; border-color: #e9ecef; }
.btn-primary { background-color: #4361ee; border-color: #4361ee; }
.btn-primary:hover { background-color: #3a56d4; border-color: #3a56d4; }
.btn-warning { background-color: #f59f00; border-color: #f59f00; color: white; }
.btn-warning:hover { background-color: #e08700; border-color: #e08700; color: white; }
.badge.bg-success { background-color: #2ecc71 !important; padding: 5px 10px; font-weight: 500; }
.password-strength-meter .progress { height: 8px; border-radius: 4px; overflow: hidden; }
.progress-bar.bg-danger { background-color: #e74c3c; }
.progress-bar.bg-warning { background-color: #f39c12; }
.progress-bar.bg-success { background-color: #2ecc71; }
hr { opacity: 0.2; margin: 1.5rem 0; }
.alert { border-radius: 8px; border: none; }
.alert-success { background-color: rgba(46, 204, 113, 0.1); border-left: 4px solid #2ecc71; color: #27ae60; }
.alert-danger { background-color: rgba(231, 76, 60, 0.1); border-left: 4px solid #e74c3c; color: #c0392b; }
.toggle-password { cursor: pointer; }
.text-muted { color: #6c757d !important; }
.font-size-sm { font-size: 0.875rem; }
.nav-tabs .nav-link { color: #6c757d; font-weight: 500; }
.nav-tabs .nav-link.active { color: #4361ee; border-bottom: 2px solid #4361ee; }
</style>
@endsection
