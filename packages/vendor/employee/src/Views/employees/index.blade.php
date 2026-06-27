@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Liste des employés</h3>
                        <p class="text-subtitle text-muted">
                            <span id="sync-status">Chargement...</span>
                            <span id="last-sync-info" class="badge bg-light text-dark ms-2"></span>
                        </p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active">Employés</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-end">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="zone_filter" class="form-label">Zone</label>
                                    <select class="form-select" id="zone_filter">
                                        <option value="">Toutes les zones</option>
                                        @foreach($zones as $zone)
                                            <option value="{{ $zone->area_id }}">{{ $zone->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="status_filter" class="form-label">Statut</label>
                                    <select class="form-select" id="status_filter">
                                        <option value="">Tous</option>
                                        <option value="active">Actif</option>
                                        <option value="inactive">Inactif</option>
                                        <option value="suspended">Suspendu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="emp_code_filter" class="form-label">Code employé</label>
                                    <input type="text" class="form-control" id="emp_code_filter" placeholder="Code...">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group text-start">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success" id="create-employee-button" data-bs-toggle="modal" data-bs-target="#createEmployeeModal">
                                            <i class="bi bi-plus-circle me-1"></i> Créer
                                        </button>
                                        <button type="button" class="btn btn-primary" id="sync_button">
                                            <i class="bi bi-arrow-repeat me-1"></i> Synchroniser
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="reset_filters">
                                            <i class="bi bi-x-circle me-1"></i> Reset Filtres
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="alert alert-light border" id="sync-status-container" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Client:</strong> <span id="client-name"></span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Dernière synchro:</strong> <span id="last-sync"></span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Total employés:</strong> <span id="total-employees"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info alert-dismissible fade show d-none" id="sync-alert" role="alert">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <strong>Synchronisation en cours...</strong> Veuillez patienter.
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table id="employees-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Nom complet</th>
                                        <th>Zone</th>
                                        <th>Département</th>
                                        <th>Téléphone</th>
                                        <th>Email</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Les données seront chargées via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Modal de création d'employé -->
<div class="modal fade" id="createEmployeeModal" tabindex="-1" aria-labelledby="createEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEmployeeModalLabel">Créer un nouvel employé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createEmployeeForm">
                <div class="modal-body">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required maxlength="100">
                                <div class="invalid-feedback" id="first_name-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Nom </label>
                                <input type="text" class="form-control" id="last_name" name="last_name"  maxlength="100">
                                <div class="invalid-feedback" id="last_name-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" maxlength="255">
                                <div class="invalid-feedback" id="email-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Téléphone <span class="text-muted">(Format: +229 XX XX XX XX)</span></label>
                                <div class="phone-input-container">
                                    <input type="tel" class="form-control intl-tel-input" id="phone" name="phone" placeholder="XX XX XX XX">
                                    <input type="hidden" id="phone_full" name="phone_full">
                                    <input type="hidden" id="phone_country_code" name="phone_country_code" value="bj">
                                </div>
                                <div class="invalid-feedback" id="phone-error"></div>
                                <!-- <small class="form-text text-muted">Format attendu: +229 XX XX XX XX (9 chiffres après +229)</small> -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="area_id" class="form-label">Zone</label>
                                <select class="form-select" id="area_id" name="area_id">
                                    <option value="">Sélectionner une zone</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->area_id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="area_id-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Département</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">Sélectionner un département</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->department_id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="department_id-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Adresse</label>
                        <textarea class="form-control" id="address" name="address" rows="2" maxlength="500"></textarea>
                        <div class="invalid-feedback" id="address-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-create-employee">
                        <span id="create-employee-text">Créer</span>
                        <span id="create-employee-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'édition d'employé -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEmployeeModalLabel">Modifier l'employé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editEmployeeForm">
                <input type="hidden" id="edit_employee_id" name="id">
                <div class="modal-body">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_first_name" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required maxlength="100">
                                <div class="invalid-feedback" id="edit_first_name-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_last_name" class="form-label">Nom </label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" maxlength="100">
                                <div class="invalid-feedback" id="edit_last_name-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" maxlength="255">
                                <div class="invalid-feedback" id="edit_email-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Téléphone <span class="text-muted">(Format: +229 XX XX XX XX)</span></label>
                                <div class="phone-input-container">
                                    <input type="tel" class="form-control intl-tel-input" id="edit_phone" name="phone" placeholder="XX XX XX XX">
                                    <input type="hidden" id="edit_phone_full" name="phone_full">
                                    <input type="hidden" id="edit_phone_country_code" name="phone_country_code" value="bj">
                                </div>
                                <div class="invalid-feedback" id="edit_phone-error"></div>
                                <small class="form-text text-muted">Format attendu: +229 XX XX XX XX (10 chiffres après +229)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_area_id" class="form-label">Zone</label>
                                <select class="form-select" id="edit_area_id" name="area_id">
                                    <option value="">Sélectionner une zone</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->area_id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="edit_area_id-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_department_id" class="form-label">Département</label>
                                <select class="form-select" id="edit_department_id" name="department_id">
                                    <option value="">Sélectionner un département</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->department_id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="edit_department_id-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Adresse</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="2" maxlength="500"></textarea>
                        <div class="invalid-feedback" id="edit_address-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-edit-employee">
                        <span id="edit-employee-text">Enregistrer</span>
                        <span id="edit-employee-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de vérification biométrique -->
<div class="modal fade" id="biometricModal" tabindex="-1" aria-labelledby="biometricModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="biometricModalLabel">
                    <i class="bi bi-fingerprint me-2"></i>
                    Vérification Biométrique
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="biometric-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des données biométriques...</p>
                </div>
                
                <div id="biometric-content" style="display: none;">
                    <div class="alert alert-success" id="biometric-success-alert" style="display: none;">
                        <i class="bi bi-check-circle me-2"></i>
                        <span id="biometric-success-message"></span>
                    </div>
                    
                    <div class="alert alert-danger" id="biometric-error-alert" style="display: none;">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <span id="biometric-error-message"></span>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Informations Employé</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>Code:</strong> <span id="biometric-emp-code" class="badge bg-secondary"></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Nom complet:</strong> <span id="biometric-emp-name"></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Email:</strong> <span id="biometric-emp-email"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="bi bi-terminal me-2"></i>Informations Terminal</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>Terminal UID:</strong> <span id="biometric-terminal-uid" class="badge bg-info"></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>ID Biométrique:</strong> <span id="biometric-id" class="badge bg-dark"></span>
                                    </div>
                                    <div class="mb-2 d-none">
                                        <strong>Mode:</strong> <span id="biometric-mode" class="badge bg-warning"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>Détails de Vérification</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <tr>
                                                    <td style="width: 30%"><strong>CPBV ID:</strong></td>
                                                    <td><code id="biometric-cpbv-id"></code></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Score de correspondance:</strong></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                                <div id="biometric-score-bar" class="progress-bar" role="progressbar" 
                                                                     style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                                    <span id="biometric-score-text"></span>
                                                                </div>
                                                            </div>
                                                            <span id="biometric-score-badge" class="badge"></span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Statut de vitalité:</strong></td>
                                                    <td>
                                                        <span id="biometric-liveness-status" class="badge"></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Hash Payload:</strong></td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control" id="biometric-hash-payload" readonly>
                                                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('biometric-hash-payload')">
                                                                <i class="bi bi-clipboard"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Signature numérique:</strong></td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control" id="biometric-digital-signature" readonly>
                                                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('biometric-digital-signature')">
                                                                <i class="bi bi-clipboard"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="refresh-biometric-btn">
                    <i class="bi bi-arrow-clockwise me-1"></i> Rafraîchir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression d'employé -->
<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEmployeeModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cet employé ?</p>
                <p><strong>Code:</strong> <span id="delete-employee-code"></span></p>
                <p><strong>Nom complet:</strong> <span id="delete-employee-name"></span></p>
                <p class="text-danger"><small>Cette action est irréversible.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-employee">
                    <span id="delete-employee-text">Supprimer</span>
                    <span id="delete-employee-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bibliothèque intl-tel-input pour les numéros de téléphone avec drapeaux -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/23.0.10/css/intlTelInput.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/23.0.10/js/intlTelInput.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Variables globales
    let employeeToDelete = null;
    let departmentsCache = {};
    let phoneInputCreate = null;
    let phoneInputEdit = null;
    
    // Initialiser les champs téléphone avec intl-tel-input
    function initPhoneInputs() {
        // Pour la modal de création
        const createPhoneInput = document.querySelector("#phone");
        phoneInputCreate = window.intlTelInput(createPhoneInput, {
            initialCountry: "bj",
            preferredCountries: ["bj", "fr", "ci", "sn", "tg", "ne", "bf", "ml", "gn", "gh", "ng", "cm", "cd", "ga", "us", "gb"],
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/23.0.10/js/utils.js",
            autoPlaceholder: "aggressive",
            formatOnDisplay: true,
            customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                if (selectedCountryData.iso2 === 'bj') {
                    return "XX XX XX XX";
                }
                return selectedCountryPlaceholder;
            }
        });
        
        // Pour la modal d'édition
        const editPhoneInput = document.querySelector("#edit_phone");
        phoneInputEdit = window.intlTelInput(editPhoneInput, {
            initialCountry: "bj",
            preferredCountries: ["bj", "fr", "ci", "sn", "tg", "ne", "bf", "ml", "gn", "gh", "ng", "cm", "cd", "ga", "us", "gb"],
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/23.0.10/js/utils.js",
            autoPlaceholder: "aggressive",
            formatOnDisplay: true,
            customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                if (selectedCountryData.iso2 === 'bj') {
                    return "XX XX XX XX";
                }
                return selectedCountryPlaceholder;
            }
        });
        
        // Empêcher la saisie de caractères non numériques
        $(createPhoneInput).on('input', function(e) {
            let value = $(this).val();
            // Garder seulement les chiffres et les espaces
            let newValue = value.replace(/[^\d\s]/g, '');
            $(this).val(newValue);
        });
        
        $(editPhoneInput).on('input', function(e) {
            let value = $(this).val();
            // Garder seulement les chiffres et les espaces
            let newValue = value.replace(/[^\d\s]/g, '');
            $(this).val(newValue);
        });
        
        // Formater le numéro pour le Bénin lors de la saisie
        $(createPhoneInput).on('keyup', function(e) {
            formatPhoneNumberInput(this, phoneInputCreate);
        });
        
        $(editPhoneInput).on('keyup', function(e) {
            formatPhoneNumberInput(this, phoneInputEdit);
        });
    }
    
    // Fonction pour formater le numéro de téléphone pendant la saisie
    function formatPhoneNumberInput(inputElement, itiInstance) {
        let value = $(inputElement).val().replace(/\D/g, '');
        const countryData = itiInstance.getSelectedCountryData();
        
        if (countryData.iso2 === 'bj' && value.length <= 9) {
            // Format béninois: XX XX XX XX
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 2 === 0) {
                    formatted += ' ';
                }
                formatted += value[i];
            }
            $(inputElement).val(formatted);
        }
    }
    
    // Fonction pour valider le numéro de téléphone
    function validatePhoneNumber(phoneInput, itiInstance) {
        const countryData = itiInstance.getSelectedCountryData();
        const dialCode = countryData.dialCode;
        const iso2 = countryData.iso2;
        
        // Obtenir le numéro complet
        const fullNumber = itiInstance.getNumber();
        const rawNumber = phoneInput.value.replace(/\D/g, '');
        
        if (!rawNumber || rawNumber.trim() === '') {
            return { valid: true, message: '' }; // Téléphone est optionnel
        }
        
        // Validation spécifique pour le Bénin
        // if (iso2 === 'bj') {
        //     if (rawNumber.length !== 10) {
        //         return { 
        //             valid: false, 
        //             message: 'Le numéro béninois doit avoir 10 chiffres (ex: 61 23 45 67 8)' 
        //         };
        //     }
            
            
        //     const mobilePrefixes = ['60', '61', '62', '63', '64', '65', '66', '67', '68', '69', '96', '97'];
        //     const prefix = rawNumber.substring(0, 2);
            
        //     if (!mobilePrefixes.includes(prefix)) {
        //         return { 
        //             valid: false, 
        //             message: 'Le numéro béninois doit commencer par 60-69, 96 ou 97' 
        //         };
        //     }
        // }
        
        // Validation générale pour les autres pays
        const fullNumberDigits = fullNumber.replace(/\D/g, '');
        if (fullNumberDigits.length < 8 || fullNumberDigits.length > 15) {
            return { 
                valid: false, 
                message: 'Le numéro de téléphone semble invalide' 
            };
        }
        
        return { valid: true, message: '', fullNumber: fullNumber, iso2: iso2 };
    }
    
    // Charger le statut de synchronisation
    loadSyncStatus();
    
    // Initialiser DataTable
    var table = $('#employees-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('employees.local') }}",
            data: function (d) {
                d.zone_id = $('#zone_filter').val();
                d.emp_code = $('#emp_code_filter').val();
                d.name = $('#name_filter').val();
                d.status = $('#status_filter').val();
            }
        },
        columns: [
            { 
                data: 'emp_code', 
                name: 'emp_code',
                width: '10%'
            },
            { 
                data: 'full_name', 
                name: 'full_name',
                width: '20%'
            },
            { 
                data: 'area_name', 
                name: 'area_name',
                width: '15%'
            },
            { 
                data: 'dept_name', 
                name: 'dept_name',
                width: '15%'
            },
            { 
                data: 'phone', 
                name: 'phone',
                width: '10%',
                render: function(data, type, row) {
                    if (!data) return '';
                    
                    // Extraire le code pays du numéro
                    const countryMap = {
                        '+229': 'bj',
                        // '+33': 'fr',
                        // '+225': 'ci',
                        // '+221': 'sn',
                        // '+228': 'tg',
                        // '+227': 'ne',
                        // '+226': 'bf',
                        // '+223': 'ml',
                        // '+224': 'gn',
                        // '+233': 'gh',
                        // '+234': 'ng',
                        // '+237': 'cm',
                        // '+243': 'cd',
                        // '+241': 'ga',
                        // '+1': 'us',
                        // '+44': 'gb'
                    };
                    
                    let countryCode = 'bj'; // Par défaut Bénin
                    for (const [dialCode, iso2] of Object.entries(countryMap)) {
                        if (data.startsWith(dialCode)) {
                            countryCode = iso2;
                            break;
                        }
                    }
                    
                    return `
                        <div class="d-flex align-items-center">
                            <span class="iti-flag ${countryCode} me-2"></span>
                            <span>${data}</span>
                        </div>
                    `;
                }
            },
            { 
                data: 'email', 
                name: 'email',
                width: '15%'
            },
            { 
                data: 'status_badge', 
                name: 'status',
                width: '10%'
            },
            {
                data: null, 
                orderable: false,
                searchable: false,
                width: '5%',
                responsivePriority: 1,
                render: function(data, type, row) {
    return `
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-warning edit-employee-btn" 
                data-id="${row.employee_id}"
                data-emp_code="${row.emp_code}"
                data-first_name="${row.first_name}"
                data-last_name="${row.last_name}"
                data-email="${row.email || ''}"
                data-phone="${row.phone || ''}"
                data-area_id="${row.area_id || ''}"
                data-area_name="${row.area_name || ''}"
                data-department_id="${row.department_id || ''}"
                data-dept_name="${row.dept_name || ''}"
                data-status="${row.status || ''}"
                data-address="${row.address || ''}">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-info biometric-btn" 
                data-emp_code="${row.emp_code}"
                data-full_name="${row.full_name}"
                title="Vérification biométrique">
                <i class="bi bi-fingerprint"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger delete-employee-btn" 
                data-id="${row.employee_id}"
                data-emp_code="${row.emp_code}"
                data-full_name="${row.full_name}">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
}
            }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
        order: [[1, 'asc']],
        responsive: true,
        drawCallback: function(settings) {
            var api = this.api();
            var pageInfo = api.page.info();
            $('.dataTables_info').html(
                'Affichage de ' + (pageInfo.start + 1) + ' à ' + 
                pageInfo.end + ' sur ' + pageInfo.recordsTotal + ' entrées'
            );
            
            // Réattacher les événements après le redessinage du tableau
            attachActionButtons();
        }
    });

    // ================================================================
    // DÉLÉGATION D'ÉVÉNEMENTS sur document
    // Couvre les boutons dans les lignes normales ET dans les child rows
    // responsive (lignes expansées sur petit écran).
    // NE PAS utiliser .off/.on direct sur les classes — ça ne marche pas
    // après que DataTables responsive re-rende les boutons dans le DOM.
    // ================================================================

    // Bouton Édition
    $(document).on('click', '.edit-employee-btn', function() {
        const employeeData = {
            id:            $(this).data('id'),
            emp_code:      $(this).data('emp_code'),
            first_name:    $(this).data('first_name'),
            last_name:     $(this).data('last_name'),
            email:         $(this).data('email'),
            phone:         $(this).data('phone'),
            area_id:       $(this).data('area_id'),
            area_name:     $(this).data('area_name'),
            department_id: $(this).data('department_id'),
            dept_name:     $(this).data('dept_name'),
            status:        $(this).data('status'),
            address:       $(this).data('address')
        };
        openEditModal(employeeData);
    });

    // Bouton Suppression
    $(document).on('click', '.delete-employee-btn', function() {
        openDeleteModal(
            $(this).data('id'),
            $(this).data('emp_code'),
            $(this).data('full_name')
        );
    });

    // Bouton Biométrique
    $(document).on('click', '.biometric-btn', function() {
        const empCode  = $(this).data('emp_code');
        const fullName = $(this).data('full_name');

        $('#biometricModal').modal('show');
        $('#biometricModalLabel').html(
            `<i class="bi bi-fingerprint me-2"></i>Vérification Biométrique - ${fullName}`
        );
        $('#biometricModal').data('emp_code', empCode);
        $('#biometricModal').data('full_name', fullName);
        loadBiometricData(empCode);
    });

    // Garder attachActionButtons() et attachBiometricButtons() vides —
    // ils sont encore appelés dans drawCallback mais n'ont plus besoin
    // de faire quoi que ce soit (la délégation couvre tout).
    function attachActionButtons() { /* délégation gérée sur document */ }
    function attachBiometricButtons() { /* délégation gérée sur document */ }

// Fonction pour charger les données biométriques
function loadBiometricData(empCode) {
    // Afficher le loader
    $('#biometric-loading').show();
    $('#biometric-content').hide();
    $('#biometric-success-alert').hide();
    $('#biometric-error-alert').hide();
    
    $.ajax({
        url: `/api/biometric/${empCode}`,
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                displayBiometricData(response);
                showBiometricSuccess('Données biométriques chargées avec succès');
            } else {
                showBiometricError(response.error || 'Erreur lors du chargement des données');
            }
        },
        error: function(xhr, status, error) {
            console.error('Biometric AJAX Error:', error);
            showBiometricError('Erreur de connexion au serveur');
        },
        complete: function() {
            $('#biometric-loading').hide();
            $('#biometric-content').show();
        }
    });
}

// Fonction pour afficher les données biométriques
function displayBiometricData(data) {
    // Informations employé
    $('#biometric-emp-code').text(data.employee_info?.code || 'N/A');
    $('#biometric-emp-name').text(
        `${data.employee_info?.last_name || ''} ${data.employee_info?.name || ''}`
    );
    $('#biometric-emp-email').text(data.employee_info?.email || 'N/A');
    
    // Informations terminal
    $('#biometric-terminal-uid').text(data.terminal_uid || 'N/A');
    $('#biometric-id').text(data.biometric_id || 'N/A');
    $('#biometric-mode').text(data.biometric_mode || 'N/A');
    
    // Détails de vérification
    $('#biometric-cpbv-id').text(data.cpbv_id || 'N/A');
    
    // Score de correspondance
    const matchingScore = data.matching_score || 0;
    const scorePercent = matchingScore * 100;
    
    $('#biometric-score-bar')
        .css('width', `${scorePercent}%`)
        .attr('aria-valuenow', scorePercent);
    
    $('#biometric-score-text').text(`${matchingScore.toFixed(3)} (${scorePercent.toFixed(1)}%)`);
    
    // Badge couleur selon le score
    let scoreBadgeClass = 'bg-danger';
    if (matchingScore >= 0.9) scoreBadgeClass = 'bg-success';
    else if (matchingScore >= 0.8) scoreBadgeClass = 'bg-warning';
    
    $('#biometric-score-badge')
        .removeClass('bg-danger bg-warning bg-success')
        .addClass(scoreBadgeClass + ' text-white')
        .text(matchingScore >= 0.95 ? 'Excellent' : 
              matchingScore >= 0.9 ? 'Bon' : 
              matchingScore >= 0.8 ? 'Moyen' : 'Faible');
    
    // Statut de vitalité
    const livenessStatus = data.liveness_status || 'UNKNOWN';
    let livenessBadgeClass = 'bg-secondary';
    let livenessText = 'Inconnu';
    
    if (livenessStatus === 'LIVE_CONFIRMED') {
        livenessBadgeClass = 'bg-success';
        livenessText = 'Vivant confirmé';
    } else if (livenessStatus === 'SPOOF_DETECTED') {
        livenessBadgeClass = 'bg-danger';
        livenessText = 'Fraude détectée';
    } else if (livenessStatus === 'LIVE_UNCONFIRMED') {
        livenessBadgeClass = 'bg-warning';
        livenessText = 'Vivant non confirmé';
    }
    
    $('#biometric-liveness-status')
        .removeClass('bg-secondary bg-success bg-danger bg-warning')
        .addClass(livenessBadgeClass + ' text-white')
        .text(livenessText);
    
    // Hash et signature
    $('#biometric-hash-payload').val(data.hash_payload || 'N/A');
    $('#biometric-digital-signature').val(data.digital_signature || 'N/A');
}

// Fonction pour afficher les messages de succès
function showBiometricSuccess(message) {
    $('#biometric-success-message').text(message);
    $('#biometric-success-alert').fadeIn();
}

// Fonction pour afficher les erreurs
function showBiometricError(message) {
    $('#biometric-error-message').text(message);
    $('#biometric-error-alert').fadeIn();
}

// Fonction utilitaire pour copier dans le clipboard
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    
    try {
        navigator.clipboard.writeText(element.value);
        showSweetAlert('success', 'Copié', 'Texte copié dans le presse-papier');
    } catch (err) {
        // Fallback pour anciens navigateurs
        document.execCommand('copy');
        showSweetAlert('success', 'Copié', 'Texte copié dans le presse-papier');
    }
}



// Gestion du bouton rafraîchir
$('#refresh-biometric-btn').on('click', function() {
    const empCode = $('#biometricModal').data('emp_code');
    if (empCode) {
        loadBiometricData(empCode);
    }
});

// Réinitialiser la modal quand elle se ferme
$('#biometricModal').on('hidden.bs.modal', function() {
    $('#biometric-loading').show();
    $('#biometric-content').hide();
    $('#biometric-success-alert').hide();
    $('#biometric-error-alert').hide();
});
    // Charger les départements pour une zone
    function loadDepartmentsForZone(zoneId, targetSelect, selectedId = null) {
        if (departmentsCache[zoneId]) {
            populateDepartmentSelect(targetSelect, departmentsCache[zoneId], selectedId);
            return;
        }
        
        $.ajax({
            url: "{{ url('departments/by-zone') }}/" + zoneId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    departmentsCache[zoneId] = response.data;
                    populateDepartmentSelect(targetSelect, response.data, selectedId);
                }
            },
            error: function() {
                // En cas d'erreur, laisser le select vide
                $(targetSelect).html('<option value="">Sélectionner un département</option>');
            }
        });
    }
    
    // Remplir le select des départements
    function populateDepartmentSelect(selectElement, departments, selectedId = null) {
        let html = '<option value="">Sélectionner un département</option>';
        
        departments.forEach(function(department) {
            const selected = department.id == selectedId ? 'selected' : '';
            html += `<option value="${department.id}" ${selected}>${department.name}</option>`;
        });
        
        $(selectElement).html(html);
    }
    
    // Ouvrir la modal d'édition
 // Ouvrir la modal d'édition
function openEditModal(employeeData) {
    console.log('=== OPEN EDIT MODAL DEBUG ===');
    console.log('Employee Data:', employeeData);
    
    // Remplir les champs du formulaire
    $('#edit_employee_id').val(employeeData.id);
    $('#edit_first_name').val(employeeData.first_name);
    $('#edit_last_name').val(employeeData.last_name);
    $('#edit_email').val(employeeData.email);
    $('#edit_address').val(employeeData.address);
    
    // Debug: Afficher les données reçues
    console.log('Area ID from data:', employeeData.area_id);
    console.log('Department ID from data:', employeeData.department_id);
    console.log('Area Name from button data:', $(this).data('area_name'));
    console.log('Dept Name from button data:', $(this).data('dept_name'));
    
    const employeeZoneId   = employeeData.area_id;
    const employeeZoneName = employeeData.area_name || '';
    const employeeDeptId   = employeeData.department_id;
    const employeeDeptName = employeeData.dept_name  || '';
    
    console.log('Final Zone Name:', employeeZoneName);
    console.log('Final Dept Name:', employeeDeptName);
    
    // Afficher les options disponibles dans les selects
    console.log('=== ZONE OPTIONS ===');
    $('#edit_area_id option').each(function() {
        console.log('Value:', $(this).val(), 'Text:', $(this).text());
    });
    
    console.log('=== DEPARTMENT OPTIONS ===');
    $('#edit_department_id option').each(function() {
        console.log('Value:', $(this).val(), 'Text:', $(this).text());
    });
    
    // Essayer de sélectionner par ID d'abord
    console.log('Trying to select zone by ID:', employeeZoneId);
    if (employeeZoneId) {
        $('#edit_area_id').val(employeeZoneId);
        const currentVal = $('#edit_area_id').val();
        console.log('After setting zone by ID, current value:', currentVal);
        
        if (currentVal !== employeeZoneId) {
            console.log('Zone ID not found, trying by name:', employeeZoneName);
            selectByText('#edit_area_id', employeeZoneName);
        } else {
            console.log('Zone selected successfully by ID');
        }
    } else if (employeeZoneName) {
        console.log('No zone ID, trying by name:', employeeZoneName);
        selectByText('#edit_area_id', employeeZoneName);
    } else {
        $('#edit_area_id').val('');
        console.log('No zone data available');
    }
    
    // Même logique pour le département
    console.log('Trying to select department by ID:', employeeDeptId);
    if (employeeDeptId) {
        $('#edit_department_id').val(employeeDeptId);
        const currentVal = $('#edit_department_id').val();
        console.log('After setting dept by ID, current value:', currentVal);
        
        if (currentVal !== employeeDeptId) {
            console.log('Dept ID not found, trying by name:', employeeDeptName);
            selectByText('#edit_department_id', employeeDeptName);
        } else {
            console.log('Dept selected successfully by ID');
        }
    } else if (employeeDeptName) {
        console.log('No dept ID, trying by name:', employeeDeptName);
        selectByText('#edit_department_id', employeeDeptName);
    } else {
        $('#edit_department_id').val('');
        console.log('No department data available');
    }
    
    // Vérifier les valeurs finales
    console.log('Final zone select value:', $('#edit_area_id').val());
    console.log('Final dept select value:', $('#edit_department_id').val());
    
    // Gérer le téléphone
    if (employeeData.phone) {
        console.log('Setting phone:', employeeData.phone);
        phoneInputEdit.setNumber(employeeData.phone);
    } else {
        phoneInputEdit.setNumber('');
    }
    
    // Réinitialiser les erreurs
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    $('#editEmployeeModal').modal('show');
    console.log('=== END DEBUG ===');
}

// Fonction utilitaire pour sélectionner une option par texte
function selectByText(selectId, text) {
    console.log(`selectByText called: ${selectId}, looking for: "${text}"`);
    
    if (!text) {
        console.log('No text provided');
        return;
    }
    
    const searchText = text.trim().toLowerCase();
    console.log('Searching for:', searchText);
    
    let found = false;
    $(selectId + ' option').each(function(index) {
        const optionText = $(this).text().trim().toLowerCase();
        console.log(`Option ${index}: "${optionText}"`);
        
        if (optionText === searchText) {
            console.log('Exact match found!');
            $(this).prop('selected', true);
            found = true;
            return false;
        }
    });
    
    // Si non trouvé, essayer une correspondance partielle
    if (!found) {
        console.log('No exact match, trying partial match...');
        $(selectId + ' option').each(function(index) {
            const optionText = $(this).text().trim().toLowerCase();
            
            if (optionText.includes(searchText) || searchText.includes(optionText)) {
                console.log(`Partial match found: "${optionText}"`);
                $(this).prop('selected', true);
                found = true;
                return false;
            }
        });
    }
    
    // Si toujours pas trouvé, désélectionner
    if (!found) {
        console.log('No match found at all');
        $(selectId).val('');
    } else {
        console.log('Match found and selected');
    }
}
    
    // Ouvrir la modal de suppression
    function openDeleteModal(id, empCode, fullName) {
        employeeToDelete = id;
        $('#delete-employee-code').text(empCode);
        $('#delete-employee-name').text(fullName);
        $('#deleteEmployeeModal').modal('show');
    }
    
    // Appliquer les filtres
    function applyFilters() {
        table.ajax.reload();
    }

    // Événements pour les filtres avec debounce
    var filterTimeout;
    $('#zone_filter, #status_filter').on('change', function() {
        applyFilters();
    });
    
    $('#emp_code_filter').on('keyup', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(applyFilters, 500);
    });

    // Réinitialiser les filtres
    $('#reset_filters').on('click', function() {
        $('#zone_filter').val('');
        $('#emp_code_filter').val('');
        $('#status_filter').val('');
        applyFilters();
    });

    // Synchronisation normale
    $('#sync_button').on('click', function() {
        performSync(false);
    });
    
    // Reset et Sync
    $('#reset_sync').on('click', function() {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Tous les employés seront supprimés et resynchronisés. Cette action est irréversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, continuer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                performSync(true);
            }
        });
    });
    
    function performSync(reset = false) {
        var $button = reset ? $('#reset_sync') : $('#sync_button');
        var originalText = $button.html();
        var action = reset ? 'reset-sync' : 'sync';
        
        $button.prop('disabled', true).html('<i class="bi bi-arrow-repeat spin"></i> En cours...');
        $('#sync-alert').removeClass('d-none');
        
        $.ajax({
            url: "{{ url('employees') }}/" + action,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    showSweetAlert('success', 
                        reset ? 'Reset & Sync réussi' : 'Synchronisation réussie', 
                        response.message
                    );
                    
                    // Recharger les données et le statut
                    table.ajax.reload();
                    setTimeout(function() {
                        loadSyncStatus();
                    }, 1000);
                } else {
                    showSweetAlert('error', 
                        'Erreur', 
                        response.message
                    );
                }
            },
            error: function(xhr) {
                showSweetAlert('error', 
                    'Erreur', 
                    'Erreur lors de l\'opération. ' + 
                    (xhr.responseJSON?.error || 'Veuillez réessayer ultérieurement.')
                );
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
                $('#sync-alert').addClass('d-none');
            }
        });
    }
    
    // Charger le statut de synchronisation
    function loadSyncStatus() {
        $.ajax({
            url: "{{ url('employees/sync-status') }}",
            type: 'GET',
            success: function(response) {
                if (response.client_name !== 'Non associé') {
                    $('#sync-status-container').show();
                    $('#client-name').text(response.client_name);
                    $('#last-sync').text(response.last_sync);
                    $('#total-employees').text(response.total_employees);
                }
                updateSyncStatusUI(response);
            }
        });
    }

    function updateSyncStatusUI(status) {
        var statusText = 'Synchronisé';
        var statusClass = 'text-success';
        var badgeClass = 'bg-success';
        var badgeText = 'À jour';
        
        if (!status.last_sync || status.last_sync === 'Jamais') {
            statusText = 'Jamais synchronisé';
            statusClass = 'text-danger';
            badgeClass = 'bg-danger';
            badgeText = 'Non sync';
        }
        
        $('#sync-status').html(statusText).removeClass('text-danger text-warning text-success').addClass(statusClass);
        $('#last-sync-info').html(badgeText).removeClass('bg-danger bg-warning bg-success bg-info bg-light').addClass(badgeClass);
    }
    
    // Gestion de la création d'employé
    $('#createEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        
        // Valider le numéro de téléphone
        const phoneValidation = validatePhoneNumber(document.querySelector("#phone"), phoneInputCreate);
        if (!phoneValidation.valid) {
            $('#phone').addClass('is-invalid');
            $('#phone-error').text(phoneValidation.message);
            return;
        }
        
        // Récupérer le numéro complet et le code pays
        const fullPhone = phoneValidation.fullNumber || '';
        const countryCode = phoneValidation.iso2 || 'bj';
        
        // Récupérer les données du formulaire
        var formData = {
            emp_code: $('#emp_code').val(),
            first_name: $('#first_name').val(),
            last_name: $('#last_name').val(),
            email: $('#email').val(),
            phone: fullPhone,
            phone_country_code: countryCode,
            area_id: $('#area_id').val(),
            department_id: $('#department_id').val(),
            status: $('#status').val(),
            address: $('#address').val(),
            _token: "{{ csrf_token() }}"
        };
        
        // Désactiver le bouton et afficher le spinner
        $('#submit-create-employee').prop('disabled', true);
        $('#create-employee-text').addClass('d-none');
        $('#create-employee-spinner').removeClass('d-none');
        
        // Réinitialiser les erreurs
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ route('employees.store') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#createEmployeeModal').modal('hide');
                    
                    // Réinitialiser le formulaire
                    $('#createEmployeeForm')[0].reset();
                    $('#department_id').html('<option value="">Sélectionner un département</option>');
                    
                    // Réinitialiser le champ téléphone
                    phoneInputCreate.setNumber('');
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Employé créé avec succès',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Recharger le tableau
                    table.ajax.reload();
                    
                    // Mettre à jour le statut de synchronisation
                    loadSyncStatus();
                } else {
                    // Afficher les erreurs
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        var input = $('#' + key);
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').text(value[0]);
                    });
                } else {
                    showSweetAlert('error', 'Erreur', 
                        'Une erreur est survenue lors de la création. ' + 
                        (xhr.responseJSON?.message || 'Veuillez réessayer.')
                    );
                }
            },
            complete: function() {
                // Réactiver le bouton
                $('#submit-create-employee').prop('disabled', false);
                $('#create-employee-text').removeClass('d-none');
                $('#create-employee-spinner').addClass('d-none');
            }
        });
    });
    
    // Gestion de l'édition d'employé
    $('#editEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        
        const employeeId = $('#edit_employee_id').val();
        
        // Valider le numéro de téléphone
        const phoneValidation = validatePhoneNumber(document.querySelector("#edit_phone"), phoneInputEdit);
        if (!phoneValidation.valid) {
            $('#edit_phone').addClass('is-invalid');
            $('#edit_phone-error').text(phoneValidation.message);
            return;
        }
        
        // Récupérer le numéro complet et le code pays
        const fullPhone = phoneValidation.fullNumber || '';
        const countryCode = phoneValidation.iso2 || 'bj';
        
        // Récupérer les données du formulaire
        var formData = {
            emp_code: $('#edit_emp_code').val(),
            first_name: $('#edit_first_name').val(),
            last_name: $('#edit_last_name').val(),
            email: $('#edit_email').val(),
            phone: fullPhone,
            phone_country_code: countryCode,
            area_id: $('#edit_area_id').val(),
            department_id: $('#edit_department_id').val(),
            address: $('#edit_address').val(),
            _token: "{{ csrf_token() }}"
        };
        
        // Désactiver le bouton et afficher le spinner
        $('#submit-edit-employee').prop('disabled', true);
        $('#edit-employee-text').addClass('d-none');
        $('#edit-employee-spinner').removeClass('d-none');
        
        // Réinitialiser les erreurs
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ url('employees') }}/" + employeeId,
            type: 'PUT',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#editEmployeeModal').modal('hide');
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Employé modifié avec succès',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Recharger le tableau
                    table.ajax.reload();
                    
                    // Mettre à jour le statut de synchronisation
                   // loadSyncStatus();
                } else {
                    // Afficher les erreurs
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        var input = $('#edit_' + key);
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').text(value[0]);
                    });
                } else {
                    showSweetAlert('error', 'Erreur', 
                        'Une erreur est survenue lors de la modification. ' + 
                        (xhr.responseJSON?.message || 'Veuillez réessayer.')
                    );
                }
            },
            complete: function() {
                // Réactiver le bouton
                $('#submit-edit-employee').prop('disabled', false);
                $('#edit-employee-text').removeClass('d-none');
                $('#edit-employee-spinner').addClass('d-none');
            }
        });
    });
    
    // Gestion de la suppression d'employé
    $('#confirm-delete-employee').on('click', function() {
        if (!employeeToDelete) return;
        
        // Désactiver le bouton et afficher le spinner
        $(this).prop('disabled', true);
        $('#delete-employee-text').addClass('d-none');
        $('#delete-employee-spinner').removeClass('d-none');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ url('employees') }}/" + employeeToDelete,
            type: 'DELETE',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#deleteEmployeeModal').modal('hide');
                    
                    // Réinitialiser la variable
                    employeeToDelete = null;
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Employé supprimé avec succès',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Recharger le tableau
                    table.ajax.reload();
                    
                    // Mettre à jour le statut de synchronisation
                 //   loadSyncStatus();
                } else {
                    // Afficher les erreurs
                    showSweetAlert('error', 'Erreur', response.message);
                    
                    // Fermer la modal
                    $('#deleteEmployeeModal').modal('hide');
                }
            },
            error: function(xhr) {
                showSweetAlert('error', 'Erreur', 
                    'Une erreur est survenue lors de la suppression. ' + 
                    (xhr.responseJSON?.message || 'Veuillez réessayer.')
                );
                
                // Fermer la modal
                $('#deleteEmployeeModal').modal('hide');
            },
            complete: function() {
                // Réactiver le bouton
                $('#confirm-delete-employee').prop('disabled', false);
                $('#delete-employee-text').removeClass('d-none');
                $('#delete-employee-spinner').addClass('d-none');
            }
        });
    });
    
    // Réinitialiser le formulaire quand la modal de création se ferme
    $('#createEmployeeModal').on('hidden.bs.modal', function() {
        $('#createEmployeeForm')[0].reset();
        $('#department_id').html('<option value="">Sélectionner un département</option>');
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#submit-create-employee').prop('disabled', false);
        $('#create-employee-text').removeClass('d-none');
        $('#create-employee-spinner').addClass('d-none');
        
        // Réinitialiser le champ téléphone
        if (phoneInputCreate) {
            phoneInputCreate.setNumber('');
            phoneInputCreate.setCountry('bj');
        }
    });
    
    // Réinitialiser quand la modal d'édition se ferme
    $('#editEmployeeModal').on('hidden.bs.modal', function() {
        $('#submit-edit-employee').prop('disabled', false);
        $('#edit-employee-text').removeClass('d-none');
        $('#edit-employee-spinner').addClass('d-none');
        
        // Réinitialiser le champ téléphone
        if (phoneInputEdit) {
            phoneInputEdit.setCountry('bj');
        }
    });
    
    // Réinitialiser quand la modal de suppression se ferme
    $('#deleteEmployeeModal').on('hidden.bs.modal', function() {
        employeeToDelete = null;
        $('#confirm-delete-employee').prop('disabled', false);
        $('#delete-employee-text').removeClass('d-none');
        $('#delete-employee-spinner').addClass('d-none');
    });
    
    function showSweetAlert(icon, title, text, timer = null) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer || 5000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        
        Toast.fire({
            icon: icon,
            title: title,
            text: text
        });
    }

    // Initialiser les champs téléphone
    initPhoneInputs();

    // Vérifier périodiquement le statut
    // setInterval(loadSyncStatus, 30000);

    $('<style>')
        .text('.spin { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }')
        .appendTo('head');
});
</script>

<style>
    .dataTables_wrapper {
        padding: 10px 0;
    }
    .dataTables_length,
    .dataTables_filter {
        margin-bottom: 15px;
    }
    .dataTables_filter input {
        margin-left: 10px;
    }
    .dt-responsive {
        width: 100% !important;
    }
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    .btn-group .btn {
        margin-right: 5px;
        border-radius: 4px !important;
    }
    .badge {
        font-size: 0.75em;
        padding: 0.35em 0.65em;
    }
    #sync-alert {
        margin-bottom: 1rem;
    }
    #sync-status-container {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
    }
    #create-employee-button {
        background-color: #198754;
        border-color: #198754;
    }
    #create-employee-button:hover {
        background-color: #157347;
        border-color: #146c43;
    }
    .modal-content {
        border-radius: 10px;
    }
    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        border-radius: 10px 10px 0 0;
    }
    .modal-title {
        color: #333;
        font-weight: 600;
    }
    .btn-group .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
    }
    .btn-warning:hover {
        background-color: #e0a800;
        border-color: #d39e00;
    }
    .modal-lg {
        max-width: 800px;
    }

    /* ── Fix modals sur petit écran / responsive ── */
    /* S'assure que la modal et son backdrop sont toujours au premier plan,
       y compris quand DataTables responsive a re-rendu le DOM */
    .modal { z-index: 1055 !important; }
    .modal-backdrop { z-index: 1050 !important; }

    /* Sur mobile, les modals prennent toute la largeur */
    @media (max-width: 576px) {
        .modal-dialog {
            margin: 0.5rem;
        }
        .modal-lg {
            max-width: 100%;
        }
    }
    
    /* Styles pour intl-tel-input */
    .intl-tel-input {
        width: 100%;
    }
    
    .intl-tel-input .flag-container {
        z-index: 3;
    }
    
    .intl-tel-input .selected-flag {
        padding: 0 10px;
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
        border-radius: 0.375rem 0 0 0.375rem;
        border-right: none;
    }
    
    .intl-tel-input .selected-flag:hover {
        background-color: #e9ecef;
    }
    
    .intl-tel-input .country-list {
        z-index: 1051 !important; /* Au-dessus des modals */
    }
    
    .intl-tel-input input {
        padding-left: 90px !important;
        height: 38px;
    }
    
    .phone-input-container {
        position: relative;
    }
    
    /* Style pour les drapeaux dans le tableau */
    .iti-flag {
        width: 20px;
        height: 15px;
        box-shadow: 0 0 1px rgba(0,0,0,0.2);
        background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/23.0.10/img/flags.png");
        background-repeat: no-repeat;
        background-color: #dbdbdb;
        background-position: 20px 0;
    }
    
    .iti-flag.bj {
        background-position: -2206px 0;
    }
    
    .iti-flag.fr {
        background-position: -952px 0;
    }
    
    .iti-flag.ci {
        background-position: -408px 0;
    }
    
    .iti-flag.sn {
        background-position: -1734px 0;
    }
    
    .iti-flag.tg {
        background-position: -2006px 0;
    }
    
    .iti-flag.ne {
        background-position: -1360px 0;
    }
    
    .iti-flag.bf {
        background-position: -136px 0;
    }
    
    .iti-flag.ml {
        background-position: -1190px 0;
    }
    
    .iti-flag.gn {
        background-position: -986px 0;
    }
    
    .iti-flag.gh {
        background-position: -918px 0;
    }
    
    .iti-flag.ng {
        background-position: -1394px 0;
    }
    
    .iti-flag.cm {
        background-position: -272px 0;
    }
    
    .iti-flag.cd {
        background-position: -306px 0;
    }
    
    .iti-flag.ga {
        background-position: -782px 0;
    }
    
    .iti-flag.us {
        background-position: -2108px 0;
    }
    
    .iti-flag.gb {
        background-position: -850px 0;
    }
    /* Ajoutez ce CSS à la fin de votre section <style> */

/* Styles pour la modal biométrique */
#biometric-loading {
    background-color: #f8f9fa;
    border-radius: 8px;
}

#biometric-content .card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

#biometric-content .card-header {
    border-radius: 6px 6px 0 0;
    font-weight: 600;
}

#biometric-score-bar {
    transition: width 0.5s ease-in-out;
}

.biometric-badge {
    padding: 0.25em 0.6em;
    font-size: 0.85em;
    font-weight: 600;
}

.input-group-sm .form-control {
    font-family: 'Courier New', monospace;
    font-size: 0.85em;
}

/* Couleurs pour les badges de statut */
.bg-live-confirmed {
    background-color: #28a745 !important;
}

.bg-spoof-detected {
    background-color: #dc3545 !important;
}

.bg-live-unconfirmed {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

/* Animation pour le spinner */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.spinner-border.text-primary {
    animation: pulse 1.5s infinite;
}

/* Responsive pour la modal */
@media (max-width: 768px) {
    #biometric-content .row > div {
        margin-bottom: 1rem;
    }
    
    #biometric-content .table-responsive {
        font-size: 0.9em;
    }
}
</style>
@endsection