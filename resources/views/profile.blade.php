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
                            Gérez vos informations personnelles et votre mot de passe
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
                <div class="row">
                    <div class="col-md-4">
                        <!-- Carte Profil Utilisateur -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Informations du profil</h4>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-column align-items-center text-center">
                                    <!-- Avatar avec première lettre du nom -->
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
                                    
                                    <!-- Nom et Email -->
                                    <div class="mt-3">
                                        <h4>{{ Auth::user()->name }}</h4>
                                        <p class="text-secondary mb-1">{{ Auth::user()->email }}</p>
                                        <p class="text-muted font-size-sm">
                                            Membre depuis {{ Auth::user()->created_at->format('d/m/Y') }}
                                        </p>
                                    </div>
                                    
                                    <!-- Statut -->
                                    <div class="mt-2">
                                        <span class="badge bg-success">Actif</span>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <!-- Informations détaillées -->
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
                                <!-- Messages de succès/erreur -->
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
                                
                                <!-- Formulaire de modification du nom -->
                                <form action="{{ route('profile.update') }}" method="POST" class="mb-4">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="update_type" value="profile">
                                    
                                    <h6 class="text-muted mb-3">Informations personnelles</h6>
                                    
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="name" class="form-label">Nom complet *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-person"></i>
                                                </span>
                                                <input type="text" 
                                                       class="form-control @error('name') is-invalid @enderror" 
                                                       id="name" 
                                                       name="name" 
                                                       value="{{ old('name', Auth::user()->name) }}" 
                                                       required>
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        
                                        <div class="form-group col-md-6">
                                            <label for="email" class="form-label">Adresse email *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-envelope"></i>
                                                </span>
                                                <input type="email" 
                                                       class="form-control @error('email') is-invalid @enderror" 
                                                       id="email" 
                                                       name="email" 
                                                       value="{{ old('email', Auth::user()->email) }}" 
                                                       required>
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
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
                                
                                <!-- Formulaire de modification du mot de passe -->
                                <form action="{{ route('profile.update') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="update_type" value="password">
                                    
                                    <h6 class="text-muted mb-3">Changer le mot de passe</h6>
                                    
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="current_password" class="form-label">Mot de passe actuel *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-lock"></i>
                                                </span>
                                                <input type="password" 
                                                       class="form-control @error('current_password') is-invalid @enderror" 
                                                       id="current_password" 
                                                       name="current_password" 
                                                       required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                @error('current_password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        
                                        <div class="form-group col-md-6">
                                            <label for="password" class="form-label">Nouveau mot de passe *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-key"></i>
                                                </span>
                                                <input type="password" 
                                                       class="form-control @error('password') is-invalid @enderror" 
                                                       id="password" 
                                                       name="password" 
                                                       required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                @error('password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-text">
                                                Minimum 8 caractères
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="form-group col-md-6">
                                            <label for="password_confirmation" class="form-label">Confirmer le mot de passe *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-shield-check"></i>
                                                </span>
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="password_confirmation" 
                                                       name="password_confirmation" 
                                                       required>
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
            </section>
        </div>
    </div>
</div>

<!-- Scripts pour la page profil -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Afficher/masquer le mot de passe
    $('.toggle-password').on('click', function() {
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
    
    // Validation du mot de passe
    $('#password').on('keyup', function() {
        const password = $(this).val();
        const strength = checkPasswordStrength(password);
        updatePasswordStrength(strength);
    });
    
    function checkPasswordStrength(password) {
        let strength = 0;
        
        // Longueur minimale
        if (password.length >= 8) strength++;
        
        // Contient des minuscules
        if (password.match(/[a-z]+/)) strength++;
        
        // Contient des majuscules
        if (password.match(/[A-Z]+/)) strength++;
        
        // Contient des chiffres
        if (password.match(/[0-9]+/)) strength++;
        
        // Contient des caractères spéciaux
        if (password.match(/[$@#&!%^*()\-_=+[\]{};:'",.<>/?\\|`~]+/)) strength++;
        
        return strength;
    }
    
    function updatePasswordStrength(strength) {
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
    
    // Générer une couleur aléatoire pour l'avatar
    function generateColorFromName(name) {
        // Liste de couleurs attrayantes
        const colors = [
            '#4361ee', '#3a56d4', // Bleu
            '#2ecc71', '#27ae60', // Vert
            '#e74c3c', '#c0392b', // Rouge
            '#f39c12', '#d35400', // Orange
            '#9b59b6', '#8e44ad', // Violet
            '#1abc9c', '#16a085', // Turquoise
            '#3498db', '#2980b9', // Bleu clair
            '#e67e22', '#d35400', // Orange foncé
        ];
        
        // Générer un index basé sur le nom
        let hash = 0;
        for (let i = 0; i < name.length; i++) {
            hash = name.charCodeAt(i) + ((hash << 5) - hash);
        }
        
        // Utiliser la valeur absolue pour éviter les index négatifs
        const index = Math.abs(hash) % colors.length;
        return colors[index];
    }
    
    // Mettre à jour la couleur de l'avatar
    const userName = "{{ Auth::user()->name }}";
    const avatarColor = generateColorFromName(userName);
    $('.avatar-initials').css('background-color', avatarColor);
});
</script>

<style>
.avatar-initials {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.avatar-initials:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.card {
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    border-top-left-radius: 10px !important;
    border-top-right-radius: 10px !important;
}

.form-control:focus {
    border-color: #4361ee;
    box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #e9ecef;
}

.btn-primary {
    background-color: #4361ee;
    border-color: #4361ee;
    padding: 8px 20px;
}

.btn-primary:hover {
    background-color: #3a56d4;
    border-color: #3a56d4;
}

.btn-warning {
    background-color: #f59f00;
    border-color: #f59f00;
    color: white;
    padding: 8px 20px;
}

.btn-warning:hover {
    background-color: #e08700;
    border-color: #e08700;
    color: white;
}

.btn-outline-secondary {
    border-color: #dee2e6;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

.badge.bg-success {
    background-color: #2ecc71 !important;
    padding: 5px 10px;
    font-weight: 500;
}

.password-strength-meter .progress {
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar.bg-danger { background-color: #e74c3c; }
.progress-bar.bg-warning { background-color: #f39c12; }
.progress-bar.bg-success { background-color: #2ecc71; }

hr {
    opacity: 0.2;
    margin: 1.5rem 0;
}

.alert {
    border-radius: 8px;
    border: none;
}

.alert-success {
    background-color: rgba(46, 204, 113, 0.1);
    border-left: 4px solid #2ecc71;
    color: #27ae60;
}

.alert-danger {
    background-color: rgba(231, 76, 60, 0.1);
    border-left: 4px solid #e74c3c;
    color: #c0392b;
}

.toggle-password {
    cursor: pointer;
}

.text-muted {
    color: #6c757d !important;
}

.font-size-sm {
    font-size: 0.875rem;
}
</style>
@endsection