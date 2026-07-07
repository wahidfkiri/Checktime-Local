@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Plannings des Employés</h3>
                        <p class="text-subtitle text-muted">Gestion des emplois du temps individuels</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('dashboard') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ url('plannings.index') }}">Plannings</a>
                                </li>
                                <li class="breadcrumb-item active">Plannings employés</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="employee_filter">Employé</label>
                                    <select class="form-control" id="employee_filter">
                                        <option value="">Tous</option>
                                        @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">
                                            {{ $employee->emp_code }} - {{ $employee->first_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="type_filter">Type</label>
                                    <select class="form-control" id="type_filter">
                                        <option value="">Tous</option>
                                        <option value="fixe">Fixe</option>
                                        <option value="rotation">Rotation</option>
                                        <option value="planifie">Planifié</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date_filter">Du</label>
                                    <input type="date" class="form-control" id="start_date_filter">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date_filter">Au</label>
                                    <input type="date" class="form-control" id="end_date_filter">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status_filter">Statut</label>
                                    <select class="form-control" id="status_filter">
                                        <option value="">Tous</option>
                                        <option value="1">Actif</option>
                                        <option value="0">Inactif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group text-start">
                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success" id="create-schedule-button" data-bs-toggle="modal" data-bs-target="#createScheduleModal">
                                            <i class="bi bi-plus-circle me-1"></i> Nouveau
                                        </button>
                                        <button type="button" class="btn btn-info" id="export_pdf_btn">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Exporter PDF
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="reset_filters">
                                            <i class="bi bi-x-circle me-1"></i> Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="schedules-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Période</th>
                                        <th>Type</th>
                                        <th>Horaire</th>
                                        <th>Heures</th>
                                        <th>Durée</th>
                                        <th>Travail</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Modal création multiple -->
<div class="modal fade" id="createScheduleModal" tabindex="-1" aria-labelledby="createScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createScheduleModalLabel">Créer des plannings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createScheduleForm">
                <div class="modal-body">
                    <!-- Section pour sélection multiple d'employés -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Sélection d'employés <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 mb-2">
                                    <select class="form-control" id="employee_id" name="employee_id">
                                        <option value="">Sélectionner un employé</option>
                                        @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">
                                            {{ $employee->emp_code }} - {{ $employee->full_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="addEmployeeBtn">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                                
                                <!-- Liste des employés sélectionnés -->
                                <div id="selectedEmployeesContainer" class="d-none">
                                    <label class="form-label">Employés sélectionnés:</label>
                                    <div id="selectedEmployeesList" class="border rounded p-2 mb-2" style="max-height: 150px; overflow-y: auto;">
                                        <!-- Les employés sélectionnés apparaîtront ici -->
                                    </div>
                                </div>
                                
                                <!-- Option pour tous les employés -->
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select_all_employees">
                                    <label class="form-check-label" for="select_all_employees">
                                        Sélectionner tous les employés
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section pour plage de dates -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Date début<span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Date fin<span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Option pour jours spécifiques -->
                    <div class="row mb-3 d-none">
                        <div class="col-md-12">
                            <label class="form-label">Jours à appliquer:</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input day-checkbox" type="checkbox" id="day_all" value="all" checked>
                                <label class="form-check-label" for="day_all">Tous les jours</label>
                            </div>
                            @foreach(['Lun' => 1, 'Mar' => 2, 'Mer' => 3, 'Jeu' => 4, 'Ven' => 5, 'Sam' => 6, 'Dim' => 7] as $dayName => $dayValue)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input day-checkbox" type="checkbox" id="day_{{ $dayValue }}" value="{{ $dayValue }}" checked>
                                <label class="form-check-label" for="day_{{ $dayValue }}">{{ $dayName }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_type" class="form-label">Type de planning <span class="text-danger">*</span></label>
                                <select class="form-control" id="schedule_type" name="schedule_type" required>
                                    <option value="planifie">Planifié</option>
                                    <option value="fixe">Fixe</option>
                                    <option value="rotation">Rotation</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="work_hour_type_id" class="form-label">Type d'horaire</label>
                                <select class="form-control" id="work_hour_type_id" name="work_hour_type_id">
                                    <option value="">Sélectionner un horaire</option>
                                    @foreach($workHourTypes as $type)
                                    <option value="{{ $type->id }}" 
                                            data-start="{{ $type->start_time }}"
                                            data-end="{{ $type->end_time }}"
                                            data-break="{{ $type->break_minutes }}">
                                        {{ $type->name }} ({{ date('H:i', strtotime($type->start_time)) }} - {{ date('H:i', strtotime($type->end_time)) }})
                                    </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Laisser vide pour horaire personnalisé</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="custom_hours_section" style="display: none;">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Heure début</label>
                                <input type="time" class="form-control" id="start_time" name="start_time">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_time" class="form-label">Heure fin</label>
                                <input type="time" class="form-control" id="end_time" name="end_time">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="break_minutes" class="form-label">Pause (min)</label>
                                <input type="number" class="form-control" id="break_minutes" name="break_minutes" min="0" max="240" value="60">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mt-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="is_working_day" name="is_working_day" checked>
                                    <label class="form-check-label" for="is_working_day">
                                        Jour travaillé
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Actif
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="override_existing" name="override_existing">
                                    <label class="form-check-label" for="override_existing">
                                        Remplacer si existe déjà
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-create-schedule">
                        <span id="create-schedule-text">Créer pour tous les sélectionnés</span>
                        <span id="create-schedule-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal édition -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editScheduleModalLabel">Modifier le planning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editScheduleForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_schedule_id">
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Employé</label>
                                <div class="form-control" id="edit_employee_name" style="background-color: #f8f9fa;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_schedule_date" class="form-label">Date début<span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_schedule_date" class="form-label">Date fin<span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_schedule_type" class="form-label">Type<span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_schedule_type" name="schedule_type" required>
                                    <option value="planifie">Planifié</option>
                                    <option value="fixe">Fixe</option>
                                    <option value="rotation">Rotation</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_work_hour_type_id" class="form-label">Type d'horaire</label>
                                <select class="form-control" id="edit_work_hour_type_id" name="work_hour_type_id">
                                    <option value="">Sélectionner un horaire</option>
                                    @foreach($workHourTypes as $type)
                                    <option value="{{ $type->id }}">
                                        {{ $type->name }} ({{ date('H:i', strtotime($type->start_time)) }} - {{ date('H:i', strtotime($type->end_time)) }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                   
                    
                    <div class="row" id="edit_custom_hours_section" style="display: none;">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_start_time" class="form-label">Heure début</label>
                                <input type="time" class="form-control" id="edit_start_time" name="start_time">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_end_time" class="form-label">Heure fin</label>
                                <input type="time" class="form-control" id="edit_end_time" name="end_time">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_break_minutes" class="form-label">Pause (min)</label>
                                <input type="number" class="form-control" id="edit_break_minutes" name="break_minutes" min="0" max="240" value="60">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="edit_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mt-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="edit_is_working_day" name="is_working_day">
                                    <label class="form-check-label" for="edit_is_working_day">
                                        Jour travaillé
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">
                                        Actif
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-edit-schedule">
                        <span id="edit-schedule-text">Modifier</span>
                        <span id="edit-schedule-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal export PDF -->
<div class="modal fade" id="exportPdfModal" tabindex="-1" aria-labelledby="exportPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportPdfModalLabel">Exporter en PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="exportPdfForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pdf_start_date" class="form-label">Date début</label>
                                <input type="date" class="form-control" id="pdf_start_date" name="start_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pdf_end_date" class="form-label">Date fin</label>
                                <input type="date" class="form-control" id="pdf_end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pdf_employee_filter" class="form-label">Employé(s)</label>
                        <select class="form-control" id="pdf_employee_filter" name="employee_id" multiple style="height: 150px;">
                            <option value="">Tous les employés</option>
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">
                                {{ $employee->emp_code }} - {{ $employee->full_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="pdf_group_by_employee" name="group_by_employee" checked>
                            <label class="form-check-label" for="pdf_group_by_employee">
                                Grouper par employé
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-export-pdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Générer PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // ============================================
    // VARIABLES GLOBALES
    // ============================================
    let table;
    let selectedEmployees = [];
    
    // ============================================
    // INITIALISATION DATATABLE
    // ============================================
    table = $('#schedules-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('employee-schedules.index') }}",
            data: function (d) {
                d.employee_id = $('#employee_filter').val();
                d.schedule_type = $('#type_filter').val();
                d.start_date = $('#start_date_filter').val();
                d.end_date = $('#end_date_filter').val();
                d.is_active = $('#status_filter').val();
            }
        },
        columns: [
            { 
                data: 'employee_name', 
                name: 'employee.full_name'
            },
            { 
                data: 'date_range',
                name: 'start_date',
                orderable: true,
                searchable: true
            },
            { 
                data: 'schedule_type_badge', 
                name: 'schedule_type'
            },
            { 
                data: 'formatted_time', 
                name: 'work_hour_type_id'
            },
            { 
                data: 'work_hour_type_name', 
                name: 'workHourType.name'
            },
            { 
                data: 'total_hours',
                orderable: false,
                searchable: false
            },
            { 
                data: 'working_day_badge', 
                name: 'is_working_day'
            },
            { 
                data: 'status_badge', 
                name: 'is_active'
            },
            { 
                data: 'actions', 
                name: 'actions',
                orderable: false, 
                searchable: false 
            }
        ],
        language: { 
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json" 
        },
        pageLength: 25,
        responsive: true,
        order: [[3, 'desc']],
        dom: '<"top"<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>>rt<"bottom"<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>>',
        drawCallback: function(settings) {
            // Initialiser les tooltips après le chargement
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
    
    // ============================================
    // GESTION DES EMPLOYÉS MULTIPLES
    // ============================================
    $('#addEmployeeBtn').on('click', function() {
        let employeeId = $('#employee_id').val();
        let employeeText = $('#employee_id option:selected').text();
        
        if (employeeId && !selectedEmployees.includes(employeeId)) {
            selectedEmployees.push(employeeId);
            updateSelectedEmployeesList();
        }
    });
    
    function updateSelectedEmployeesList() {
        let listHtml = '';
        
        selectedEmployees.forEach((id, index) => {
            let employeeText = $(`#employee_id option[value="${id}"]`).text();
            listHtml += `
                <div class="selected-employee-item d-flex justify-content-between align-items-center mb-1 p-1 bg-light rounded">
                    <span>${employeeText}</span>
                    <button type="button" class="btn btn-sm btn-danger remove-employee-btn" data-index="${index}">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;
        });
        
        $('#selectedEmployeesList').html(listHtml);
        
        if (selectedEmployees.length > 0) {
            $('#selectedEmployeesContainer').removeClass('d-none');
        } else {
            $('#selectedEmployeesContainer').addClass('d-none');
        }
    }
    
    $(document).on('click', '.remove-employee-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        let index = $(this).data('index');
        if (index !== undefined && selectedEmployees[index]) {
            selectedEmployees.splice(index, 1);
            updateSelectedEmployeesList();
        }
    });
    
    $('#select_all_employees').on('change', function() {
        if ($(this).is(':checked')) {
            selectedEmployees = [];
            $('#employee_id option').each(function() {
                if ($(this).val()) {
                    selectedEmployees.push($(this).val());
                }
            });
            updateSelectedEmployeesList();
            $('#employee_id').prop('disabled', true);
            $('#addEmployeeBtn').prop('disabled', true);
        } else {
            selectedEmployees = [];
            updateSelectedEmployeesList();
            $('#employee_id').prop('disabled', false);
            $('#addEmployeeBtn').prop('disabled', false);
        }
    });
    
    // ============================================
    // GESTION DU TYPE DE PLANNING
    // ============================================
    $('#schedule_type').on('change', function() {
        handleScheduleTypeChange();
    });
    
    function handleScheduleTypeChange() {
        const scheduleType = $('#schedule_type').val();
        const workHourTypeSection = $('.col-md-6:has(#work_hour_type_id)'); // Section type horaire
        const customHoursSection = $('#custom_hours_section');
        const daysSelectionSection = $('.row.mb-3:has(.day-checkbox)'); // Section jours existante
        
        // Supprimer les sections dynamiques
        $('#days_fields_container').remove();
        $('#rotation_fields_container').remove();
        
        if (scheduleType === 'fixe') {
            // Pour "Fixe": cacher type horaire, afficher sélection des jours
            workHourTypeSection.addClass('d-none');
            customHoursSection.hide();
            daysSelectionSection.removeClass('d-none');
            $('#day_all').prop('checked', false);
            
            // Décocher tous les jours par défaut
            $('.day-checkbox').not('#day_all').prop('checked', false);
            
            // Réinitialiser le type d'horaire
            $('#work_hour_type_id').val('');
            
            // Créer un container pour les champs heures par jour
            daysSelectionSection.after(`
                <div class="row mb-3" id="days_fields_container">
                    <div class="col-md-12">
                        <div class="alert alert-info mb-2">
                            <small>
                                <i class="bi bi-info-circle"></i> 
                                Sélectionnez les jours souhaités et définissez les heures pour chaque jour.
                            </small>
                        </div>
                        <div class="days-time-grid mb-3" id="days_time_grid">
                            <!-- Les champs heures seront ajoutés dynamiquement ici -->
                        </div>
                    </div>
                </div>
            `);
            
            // Gestionnaire pour les changements de sélection des jours
            $('.day-checkbox').not('#day_all').off('change').on('change', function() {
                const dayValue = $(this).val();
                const dayName = $(this).next('label').text().trim();
                const isChecked = $(this).is(':checked');
                
                if (isChecked) {
                    // Ajouter les champs heures pour ce jour
                    $('#days_time_grid').append(`
                        <div class="day-time-item mb-2 p-2 border rounded" id="day_${dayValue}_fields">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">${dayName}</label>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <label class="form-label small">Heure début <span class="text-danger">*</span></label>
                                        <input type="time" 
                                               class="form-control day-start-time" 
                                               id="day_${dayValue}_start" 
                                               name="day_${dayValue}_start" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <label class="form-label small">Heure fin <span class="text-danger">*</span></label>
                                        <input type="time" 
                                               class="form-control day-end-time" 
                                               id="day_${dayValue}_end" 
                                               name="day_${dayValue}_end" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger remove-day-time"
                                            data-day="${dayValue}">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `);
                } else {
                    // Retirer les champs heures pour ce jour
                    $(`#day_${dayValue}_fields`).remove();
                }
                
                // Gérer l'état de "Tous les jours" en fonction des sélections
                updateAllDaysCheckbox();
            });
            
        } else if (scheduleType === 'rotation') {
            // Pour "Rotation": cacher type horaire et jours, afficher champs rotation
            workHourTypeSection.addClass('d-none');
            customHoursSection.hide();
            daysSelectionSection.addClass('d-none');
            
            // Réinitialiser le type d'horaire
            $('#work_hour_type_id').val('');
            
            // Créer un container pour les champs de rotation
            workHourTypeSection.parent().after(`
                <div class="row mb-3" id="rotation_fields_container">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="work_days_count" class="form-label">Nombre de jours de travail <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   id="work_days_count" 
                                   name="work_days_count" 
                                   min="1" 
                                   max="30" 
                                   value="5"
                                   required>
                            <div class="form-text">Nombre consécutif de jours de travail</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="rest_days_count" class="form-label">Nombre de jours de repos <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   id="rest_days_count" 
                                   name="rest_days_count" 
                                   min="1" 
                                   max="30" 
                                   value="2"
                                   required>
                            <div class="form-text">Nombre consécutif de jours de repos</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="daily_hours" class="form-label">Nombre d'heures par jour <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   id="daily_hours" 
                                   name="daily_hours" 
                                   min="1" 
                                   max="24" 
                                   step="0.5"
                                   value="8"
                                   required>
                            <div class="form-text">Exemple: 8 pour 8 heures par jour, 24 pour 24h/24</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_hour" class="form-label">Heure de début de journée <span class="text-danger">*</span></label>
                            <input type="time" 
                                   class="form-control" 
                                   id="start_hour" 
                                   name="start_hour" 
                                   value="08:00"
                                   required>
                            <div class="form-text">Heure à laquelle commence la journée de travail</div>
                        </div>
                    </div>
                </div>
            `);
            
            // Calculer automatiquement l'heure de fin quand l'heure de début ou le nombre d'heures change
            $('#start_hour, #daily_hours').on('change', function() {
                calculateEndTime();
            });
            
        } else {
            // Pour "Planifié": afficher type horaire, cacher sélection des jours
            daysSelectionSection.addClass('d-none');
            $('#days_fields_container').remove();
            $('#rotation_fields_container').remove();
            
            // Cacher les champs horaires personnalisés si un type d'horaire est sélectionné
            if ($('#work_hour_type_id').val()) {
                customHoursSection.hide();
            } else {
                customHoursSection.show();
            }
            
            // Décocher tous les jours
            $('.day-checkbox').not('#day_all').prop('checked', false);
            $('#day_all').prop('checked', false);
            
            // Cacher aussi les champs heure début/fin/pause si on revient à "Planifié"
            if (scheduleType === 'planifie') {
                $('#work_hour_type_id').val('');
                customHoursSection.hide();
                // Réinitialiser les champs heures
                $('#start_time').val('');
                $('#end_time').val('');
                $('#break_minutes').val('60');
            }
        }
    }
    
    function updateAllDaysCheckbox() {
        const checkedDays = $('.day-checkbox').not('#day_all').filter(':checked').length;
        const totalDays = $('.day-checkbox').not('#day_all').length;
        
        if (checkedDays === totalDays) {
            $('#day_all').prop('checked', true);
        } else {
            $('#day_all').prop('checked', false);
        }
    }
    
    // Calculer l'heure de fin basée sur l'heure de début et le nombre d'heures
    function calculateEndTime() {
        const startHour = $('#start_hour').val();
        const dailyHours = parseFloat($('#daily_hours').val());
        
        if (startHour && dailyHours) {
            const [hours, minutes] = startHour.split(':').map(Number);
            let endHour = hours + Math.floor(dailyHours);
            let endMinutes = minutes + ((dailyHours % 1) * 60);
            
            // Gérer les minutes supérieures à 60
            if (endMinutes >= 60) {
                endHour += 1;
                endMinutes -= 60;
            }
            
            // Gérer les heures supérieures à 24
            if (endHour >= 24) {
                endHour -= 24;
            }
            
            // Formater en HH:MM
            const endTime = endHour.toString().padStart(2, '0') + ':' + 
                          Math.round(endMinutes).toString().padStart(2, '0');
            
            // Afficher l'heure calculée
            $('#rotation_fields_container').find('.alert').append(`
                <div class="mt-2">
                    <small><i class="bi bi-clock"></i> Heure de fin calculée: <strong>${endTime}</strong></small>
                </div>
            `);
        }
    }
    
    // Gestionnaire pour "Tous les jours"
    $('#day_all').on('change', function() {
        const isChecked = $(this).is(':checked');
        const scheduleType = $('#schedule_type').val();
        
        if (scheduleType === 'fixe') {
            if (isChecked) {
                // Cocher tous les jours et ajouter les champs heures
                $('.day-checkbox').not('#day_all').prop('checked', true);
                $('.day-checkbox').not('#day_all').trigger('change');
            } else {
                // Décocher tous les jours et retirer les champs heures
                $('.day-checkbox').not('#day_all').prop('checked', false);
                $('.day-checkbox').not('#day_all').trigger('change');
            }
        }
    });
    
    // Gestionnaire pour supprimer les champs heures d'un jour
    $(document).on('click', '.remove-day-time', function() {
        const dayValue = $(this).data('day');
        $(`#day_${dayValue}`).prop('checked', false);
        $(`#day_${dayValue}_fields`).remove();
        updateAllDaysCheckbox();
    });
    
    // ============================================
    // GESTION DES HORAIRES
    // ============================================
    $('#work_hour_type_id').on('change', function() {
        if ($(this).val()) {
            let selectedOption = $(this).find('option:selected');
            $('#start_time').val(selectedOption.data('start'));
            $('#end_time').val(selectedOption.data('end'));
            $('#break_minutes').val(selectedOption.data('break'));
            $('#custom_hours_section').hide();
        } else {
            $('#custom_hours_section').show();
        }
    });
    
    $('#edit_work_hour_type_id').on('change', function() {
        if ($(this).val()) {
            $('#edit_custom_hours_section').hide();
        } else {
            $('#edit_custom_hours_section').show();
        }
    });
    
    // ============================================
    // VALIDATION POUR LES TYPES FIXE ET ROTATION
    // ============================================
    function validateScheduleForm() {
        const scheduleType = $('#schedule_type').val();
        
        if (scheduleType === 'fixe') {
            const selectedDays = $('.day-checkbox').not('#day_all').filter(':checked');
            
            // Vérifier qu'au moins un jour est sélectionné
            if (selectedDays.length === 0) {
                Swal.fire('Erreur', 'Veuillez sélectionner au moins un jour pour le planning fixe', 'error');
                return false;
            }
            
            // Vérifier que les heures sont définies pour chaque jour sélectionné
            let isValid = true;
            let errorMessages = [];
            
            selectedDays.each(function() {
                const dayValue = $(this).val();
                const startTime = $(`#day_${dayValue}_start`).val();
                const endTime = $(`#day_${dayValue}_end`).val();
                const dayName = $(this).next('label').text().trim();
                
                if (!startTime || !endTime) {
                    errorMessages.push(`Veuillez définir les heures pour ${dayName}`);
                    isValid = false;
                } else if (startTime >= endTime) {
                    errorMessages.push(`L'heure de début doit être antérieure à l'heure de fin pour ${dayName}`);
                    isValid = false;
                }
            });
            
            if (!isValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur de validation',
                    html: errorMessages.join('<br>')
                });
            }
            
            return isValid;
            
        } else if (scheduleType === 'rotation') {
            // Validation pour le type rotation
            const workDaysCount = $('#work_days_count').val();
            const restDaysCount = $('#rest_days_count').val();
            const dailyHours = $('#daily_hours').val();
            const startHour = $('#start_hour').val();
            
            let isValid = true;
            let errorMessages = [];
            
            if (!workDaysCount || workDaysCount < 1) {
                errorMessages.push('Le nombre de jours de travail doit être au moins 1');
                isValid = false;
            }
            
            if (!restDaysCount || restDaysCount < 1) {
                errorMessages.push('Le nombre de jours de repos doit être au moins 1');
                isValid = false;
            }
            
            if (!dailyHours || dailyHours < 1 || dailyHours > 24) {
                errorMessages.push('Le nombre d\'heures par jour doit être entre 1 et 24');
                isValid = false;
            }
            
            if (!startHour) {
                errorMessages.push('Veuillez définir l\'heure de début de journée');
                isValid = false;
            }
            
            if (!isValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur de validation',
                    html: errorMessages.join('<br>')
                });
            }
            
            return isValid;
        }
        
        return true;
    }
    
    // ============================================
    // CREATE - Création multiple
    // ============================================
    $('#createScheduleForm').on('submit', function(e) {
        e.preventDefault();
        
        const scheduleType = $('#schedule_type').val();
        
        // Validation spécifique selon le type
        if (!validateScheduleForm()) {
            return;
        }
        
        // Récupérer les données selon le type
        let scheduleData = {};
        
        if (scheduleType === 'fixe') {
            // Données pour type fixe
            let scheduleDaysData = [];
            $('.day-checkbox').not('#day_all').filter(':checked').each(function() {
                const dayValue = $(this).val();
                const dayName = $(this).next('label').text().trim();
                const startTime = $(`#day_${dayValue}_start`).val();
                const endTime = $(`#day_${dayValue}_end`).val();
                
                scheduleDaysData.push({
                    day_of_week: parseInt(dayValue),
                    day_name: dayName,
                    start_time: startTime,
                    end_time: endTime
                });
            });
            
            // Si "Tous les jours" est coché, inclure tous les jours
            if ($('#day_all').is(':checked')) {
                scheduleDaysData = [];
                for (let i = 1; i <= 7; i++) {
                    const startTime = $(`#day_${i}_start`).val();
                    const endTime = $(`#day_${i}_end`).val();
                    
                    if (startTime && endTime) {
                        scheduleDaysData.push({
                            day_of_week: i,
                            day_name: $(`label[for="day_${i}"]`).text().trim(),
                            start_time: startTime,
                            end_time: endTime
                        });
                    }
                }
            }
            
            scheduleData.schedule_days = scheduleDaysData;
            
        } else if (scheduleType === 'rotation') {
            // Données pour type rotation
            scheduleData.work_days_count = $('#work_days_count').val();
            scheduleData.rest_days_count = $('#rest_days_count').val();
            scheduleData.daily_hours = $('#daily_hours').val();
            scheduleData.start_hour = $('#start_hour').val();
            
            // Calculer l'heure de fin basée sur le nombre d'heures
            const startHour = scheduleData.start_hour;
            const dailyHours = parseFloat(scheduleData.daily_hours);
            
            if (startHour && dailyHours) {
                const [hours, minutes] = startHour.split(':').map(Number);
                
                // Calculer l'heure de fin
                let endHour = hours + Math.floor(dailyHours);
                let endMinutes = minutes + ((dailyHours % 1) * 60);
                
                // Gérer les minutes supérieures à 60
                if (endMinutes >= 60) {
                    endHour += 1;
                    endMinutes -= 60;
                }
                
                // Gérer les heures supérieures à 24
                if (endHour >= 24) {
                    endHour -= 24;
                }
                
                // Formater en HH:MM
                const endTime = endHour.toString().padStart(2, '0') + ':' + 
                              Math.round(endMinutes).toString().padStart(2, '0');
                
                scheduleData.start_time = startHour;
                scheduleData.end_time = endTime;
                scheduleData.break_minutes = 0; // Pas de pause pour rotation
            }
            
        } else if (scheduleType === 'planifie') {
            // Données pour type planifié
            scheduleData.work_hour_type_id = $('#work_hour_type_id').val() || null;
            scheduleData.start_time = $('#start_time').val() || null;
            scheduleData.end_time = $('#end_time').val() || null;
            scheduleData.break_minutes = $('#break_minutes').val() || 0;
            
            // Inclure les jours si spécifiés (optionnel pour planifié)
            let daysOfWeek = [];
            if (!$('#day_all').is(':checked')) {
                $('.day-checkbox').not('#day_all').filter(':checked').each(function() {
                    daysOfWeek.push(parseInt($(this).val()));
                });
            }
            if (daysOfWeek.length > 0) {
                scheduleData.days_of_week = daysOfWeek;
            }
        }
        
        // Vérifier si on utilise tous les employés ou une sélection
        let employeeIds = $('#select_all_employees').is(':checked') 
            ? 'all' 
            : (selectedEmployees.length > 0 ? selectedEmployees : [$('#employee_id').val()]);
        
        if (!employeeIds || (Array.isArray(employeeIds) && employeeIds.length === 0)) {
            Swal.fire('Erreur', 'Veuillez sélectionner au moins un employé', 'error');
            return;
        }
        
        // Validation des dates
        let startDate = $('#start_date').val();
        let endDate = $('#end_date').val();
        
        if (!startDate || !endDate) {
            Swal.fire('Erreur', 'Veuillez sélectionner une plage de dates', 'error');
            return;
        }
        
        if (new Date(endDate) < new Date(startDate)) {
            Swal.fire('Erreur', 'La date de fin doit être postérieure à la date de début', 'error');
            return;
        }
        
        // Préparer les données finales
        let formData = {
            employee_ids: employeeIds,
            start_date: startDate,
            end_date: endDate,
            schedule_type: scheduleType,
            is_working_day: $('#is_working_day').is(':checked') ? 1 : 0,
            is_active: $('#is_active').is(':checked') ? 1 : 0,
            notes: $('#notes').val(),
            override_existing: $('#override_existing').is(':checked') ? 1 : 0,
            _token: "{{ csrf_token() }}",
            ...scheduleData
        };
        
        // Supprimer les champs vides
        Object.keys(formData).forEach(key => {
            if (formData[key] === '' || formData[key] === null || 
                (Array.isArray(formData[key]) && formData[key].length === 0)) {
                delete formData[key];
            }
        });
        
        // Envoyer la requête
        $('#submit-create-schedule').prop('disabled', true);
        $('#create-schedule-text').addClass('d-none');
        $('#create-schedule-spinner').removeClass('d-none');
        
        $.ajax({
            url: "{{ route('employee-schedules.bulk-create') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#createScheduleModal').modal('hide');
                    $('#createScheduleForm')[0].reset();
                    selectedEmployees = [];
                    updateSelectedEmployeesList();
                    $('#select_all_employees').prop('checked', false);
                    $('#employee_id').prop('disabled', false);
                    $('#addEmployeeBtn').prop('disabled', false);
                    
                    // Réinitialiser l'affichage
                    $('.row.mb-3:has(.day-checkbox)').addClass('d-none');
                    $('#days_fields_container').remove();
                    $('#rotation_fields_container').remove();
                    $('#custom_hours_section').hide();
                    $('.col-md-6:has(#work_hour_type_id)').removeClass('d-none');
                    
                    // Réinitialiser le type à "Planifié"
                    $('#schedule_type').val('planifie');
                    
                    // Réinitialiser les valeurs par défaut pour rotation
                    if ($('#work_days_count').length) {
                        $('#work_days_count').val('5');
                        $('#rest_days_count').val('2');
                        $('#daily_hours').val('8');
                        $('#start_hour').val('08:00');
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        html: `<strong>${response.created} plannings créés</strong><br>
                               ${response.skipped} plannings non créés (déjà existants)<br>
                               ${response.employees_count} employés concernés`,
                        timer: 5000,
                        showConfirmButton: true
                    });
                    
                    table.ajax.reload();
                } else {
                    if (response.errors) {
                        showValidationErrors(response.errors);
                    } else {
                        Swal.fire('Erreur', response.message || 'Une erreur est survenue', 'error');
                    }
                }
            },
            error: function(xhr) {
                Swal.fire('Erreur', 'Une erreur est survenue lors de la création', 'error');
            },
            complete: function() {
                $('#submit-create-schedule').prop('disabled', false);
                $('#create-schedule-text').removeClass('d-none');
                $('#create-schedule-spinner').addClass('d-none');
            }
        });
    });
    
    // ============================================
    // EDIT - Modification
    // ============================================
    $(document).on('click', '.edit-schedule-btn', function() {
        let scheduleId = $(this).data('id');
        
        $.ajax({
            url: "{{ url('employee-schedules') }}/" + scheduleId + "/edit",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    let schedule = response.data;
                    
                    function formatForDateInput(isoDate) {
                        return new Date(isoDate.split('.')[0] + 'Z').toISOString().slice(0, 10);
                    }

                    // Remplir le formulaire d'édition
                    $('#edit_schedule_id').val(schedule.id);
                    $('#edit_employee_name').text(schedule.employee.first_name);
                    $('#edit_start_date').val(formatForDateInput(schedule.start_date));
                    $('#edit_end_date').val(formatForDateInput(schedule.end_date));
                    $('#edit_schedule_type').val(schedule.schedule_type);
                    $('#edit_work_hour_type_id').val(schedule.work_hour_type_id);
                    $('#edit_day_of_week').val(schedule.day_of_week);
                    $('#edit_start_time').val(schedule.start_time);
                    $('#edit_end_time').val(schedule.end_time);
                    $('#edit_break_minutes').val(schedule.break_minutes);
                    $('#edit_is_working_day').prop('checked', schedule.is_working_day);
                    $('#edit_is_active').prop('checked', schedule.is_active);
                    $('#edit_notes').val(schedule.notes);
                    
                    // Afficher/masquer les champs personnalisés
                    if (schedule.work_hour_type_id) {
                        $('#edit_custom_hours_section').hide();
                    } else {
                        $('#edit_custom_hours_section').show();
                    }
                    
                    $('#editScheduleModal').modal('show');
                } else {
                    Swal.fire('Erreur', response.message, 'error');
                }
            }
        });
    });
    
    // Soumission du formulaire d'édition
    $('#editScheduleForm').on('submit', function(e) {
        e.preventDefault();
         // Validation des dates
        let startDate = $('#edit_start_date').val();
        let endDate = $('#edit_end_date').val();
        
        if (!startDate || !endDate) {
            Swal.fire('Erreur', 'Veuillez sélectionner une plage de dates', 'error');
            return;
        }
        
        if (new Date(endDate) < new Date(startDate)) {
            Swal.fire('Erreur', 'La date de fin doit être postérieure à la date de début', 'error');
            return;
        }
        
        let scheduleId = $('#edit_schedule_id').val();
        
        let formData = {
            schedule_type: $('#edit_schedule_type').val(),
            work_hour_type_id: $('#edit_work_hour_type_id').val() || null,
            day_of_week: $('#edit_day_of_week').val(),
            start_date: startDate,
            end_date: endDate,
            start_time: $('#edit_start_time').val() || null,
            end_time: $('#edit_end_time').val() || null,
            break_minutes: $('#edit_break_minutes').val() || 0,
            is_working_day: $('#edit_is_working_day').is(':checked') ? 1 : 0,
            is_active: $('#edit_is_active').is(':checked') ? 1 : 0,
            notes: $('#edit_notes').val(),
            _token: "{{ csrf_token() }}",
            _method: 'PUT'
        };
        
        // Supprimer les champs vides
        Object.keys(formData).forEach(key => {
            if (formData[key] === '' || formData[key] === null) {
                delete formData[key];
            }
        });
        
        $('#submit-edit-schedule').prop('disabled', true);
        $('#edit-schedule-text').addClass('d-none');
        $('#edit-schedule-spinner').removeClass('d-none');
        
        $.ajax({
            url: "{{ url('employee-schedules') }}/" + scheduleId,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#editScheduleModal').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message,
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    table.ajax.reload();
                } else {
                    showValidationErrors(response.errors);
                }
            },
            error: function(xhr) {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
            },
            complete: function() {
                $('#submit-edit-schedule').prop('disabled', false);
                $('#edit-schedule-text').removeClass('d-none');
                $('#edit-schedule-spinner').addClass('d-none');
            }
        });
    });
    
    // ============================================
    // DELETE - Suppression
    // ============================================
    $(document).on('click', '.delete-schedule-btn', function() {
        let scheduleId = $(this).data('id');
        let employeeName = $(this).data('employee');
        let scheduleDate = $(this).data('date');
        
        Swal.fire({
            title: 'Confirmer la suppression',
            html: `Êtes-vous sûr de vouloir supprimer le planning de <strong>${employeeName}</strong> du <strong>${scheduleDate}</strong> ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('employee-schedules') }}/" + scheduleId,
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}",
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Succès',
                                text: response.message,
                                timer: 3000,
                                showConfirmButton: false
                            });
                            
                            table.ajax.reload();
                        } else {
                            Swal.fire('Erreur', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                    }
                });
            }
        });
    });
    
    // ============================================
    // EXPORT PDF
    // ============================================
    $('#export_pdf_btn').on('click', function() {
        let today = new Date();
        let firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        let lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        $('#pdf_start_date').val(firstDay.toISOString().split('T')[0]);
        $('#pdf_end_date').val(lastDay.toISOString().split('T')[0]);
        $('#pdf_employee_filter').val('');
        $('#exportPdfModal').modal('show');
    });
    
    $('#exportPdfForm').on('submit', function(e) {
        e.preventDefault();
        
        let startDate = $('#pdf_start_date').val();
        let endDate = $('#pdf_end_date').val();
        let employeeIds = $('#pdf_employee_filter').val();
        let groupByEmployee = $('#pdf_group_by_employee').is(':checked');
        
        if (!startDate || !endDate) {
            Swal.fire('Erreur', 'Veuillez sélectionner une plage de dates', 'error');
            return;
        }
        
        if (new Date(endDate) < new Date(startDate)) {
            Swal.fire('Erreur', 'La date de fin doit être postérieure à la date de début', 'error');
            return;
        }
        
        let formData = {
            format: 'pdf',
            start_date: startDate,
            end_date: endDate,
            group_by_employee: groupByEmployee ? 1 : 0,
            _token: "{{ csrf_token() }}"
        };
        
        // Ajouter les IDs d'employés si sélectionnés
        if (employeeIds && employeeIds.length > 0 && employeeIds[0] !== '') {
            formData.employee_ids = employeeIds;
        }
        
        $('#submit-export-pdf').prop('disabled', true);
        
        Swal.fire({
            title: 'Génération du PDF',
            text: 'Veuillez patienter...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: "{{ route('employee-schedules.export') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    // Télécharger le PDF via URL
                    let link = document.createElement('a');
                    link.href = response.download_url;
                    link.download = response.filename;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    $('#exportPdfModal').modal('hide');
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'PDF généré avec succès',
                        html: `Fichier téléchargé: <strong>${response.filename}</strong><br>
                               <br>
                               <small>${response.data.total_schedules} plannings exportés<br>
                               ${response.data.total_employees} employés</small>`,
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        html: response.message || 'Erreur lors de la génération du PDF',
                        showConfirmButton: true
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                
                let errorMessage = 'Une erreur est survenue lors de la génération du PDF';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.statusText) {
                    errorMessage = xhr.statusText;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur ' + xhr.status,
                    text: errorMessage,
                    showConfirmButton: true
                });
            },
            complete: function() {
                $('#submit-export-pdf').prop('disabled', false);
            }
        });
    });
    
    // ============================================
    // FILTRES
    // ============================================
    $('#employee_filter, #type_filter, #start_date_filter, #end_date_filter, #status_filter').on('change', function() {
        table.ajax.reload();
    });
    
    $('#reset_filters').on('click', function() {
        $('#employee_filter').val('');
        $('#type_filter').val('');
        $('#start_date_filter').val('');
        $('#end_date_filter').val('');
        $('#status_filter').val('');
        table.ajax.reload();
    });
    
    // ============================================
    // INITIALISATION DES DATES
    // ============================================
    function initializeDates() {
        let today = new Date();
        let firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        let lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        $('#start_date_filter').val(firstDay.toISOString().split('T')[0]);
        $('#end_date_filter').val(lastDay.toISOString().split('T')[0]);
        $('#start_date').val(today.toISOString().split('T')[0]);
        $('#end_date').val(today.toISOString().split('T')[0]);
    }
    
    // ============================================
    // INITIALISATION AU CHARGEMENT
    // ============================================
    function initialize() {
        initializeDates();
        
        // Initialiser les tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Décocher "Tous les jours" au chargement
        $('#day_all').prop('checked', false);
        
        // Initialiser avec le type "Planifié"
        handleScheduleTypeChange();
    }
    
    initialize();
    
    // ============================================
    // FONCTIONS UTILITAIRES
    // ============================================
    function showValidationErrors(errors) {
        if (typeof errors === 'string') {
            Swal.fire('Erreur', errors, 'error');
            return;
        }
        
        let errorHtml = '<ul class="text-start">';
        if (typeof errors === 'object') {
            $.each(errors, function(field, messages) {
                if (Array.isArray(messages)) {
                    $.each(messages, function(index, message) {
                        errorHtml += `<li>${field}: ${message}</li>`;
                    });
                } else {
                    errorHtml += `<li>${field}: ${messages}</li>`;
                }
            });
        }
        errorHtml += '</ul>';
        
        Swal.fire({
            icon: 'error',
            title: 'Erreur de validation',
            html: errorHtml
        });
    }
    
    // Réinitialiser le formulaire de création
    $('#createScheduleModal').on('hidden.bs.modal', function() {
        $('#createScheduleForm')[0].reset();
        selectedEmployees = [];
        updateSelectedEmployeesList();
        $('#select_all_employees').prop('checked', false);
        $('#employee_id').prop('disabled', false);
        $('#addEmployeeBtn').prop('disabled', false);
        
        // Réinitialiser l'affichage
        $('.row.mb-3:has(.day-checkbox)').addClass('d-none');
        $('#days_fields_container').remove();
        $('#rotation_fields_container').remove();
        $('#custom_hours_section').hide();
        $('.col-md-6:has(#work_hour_type_id)').removeClass('d-none');
        
        // Réinitialiser le type à "Planifié"
        $('#schedule_type').val('planifie');
        
        // Réinitialiser les valeurs par défaut
        $('#break_minutes').val('60');
        $('#work_days_count').val('5');
        $('#rest_days_count').val('2');
        $('#daily_hours').val('8');
        $('#start_hour').val('08:00');
        
        // Décocher tous les jours
        $('.day-checkbox').not('#day_all').prop('checked', false);
        $('#day_all').prop('checked', false);
        
        initializeDates();
    });
    
    // Réinitialiser le formulaire d'édition
    $('#editScheduleModal').on('hidden.bs.modal', function() {
        $('#editScheduleForm')[0].reset();
    });
});
</script>

<style>
.days-time-grid {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 10px;
    background-color: #f8f9fa;
}

.day-time-item {
    background-color: white;
    transition: all 0.3s ease;
}

.day-time-item:hover {
    background-color: #f8f9fa;
}

.remove-day-time {
    margin-top: 1.5rem;
}

/* Styles pour les jours cochés */
.day-checkbox:checked + label {
    font-weight: bold;
    color: #0d6efd;
}

/* Ajustement pour les champs inline des jours */
.form-check-inline {
    margin-right: 1.5rem;
    margin-bottom: 0.5rem;
}

/* Styles pour les champs rotation */
#rotation_fields_container .alert {
    margin-top: 15px;
}

#rotation_fields_container .form-text {
    font-size: 0.85rem;
    color: #6c757d;
}

.selected-employee-item {
    transition: all 0.2s ease;
}

.selected-employee-item:hover {
    background-color: #f1f3f4;
}
</style>
@endsection