@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Gestion des Jours Fériés</h3>
                        <p class="text-subtitle text-muted">Jours fériés, chômés et calendaires — pris en compte dans les plannings et les rapports.</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Jours Fériés</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="year_filter">Année</label>
                                    <select class="form-control" id="year_filter">
                                        <option value="">Toutes les années</option>
                                        @php $currentYear = date('Y'); @endphp
                                        @for($y = $currentYear + 2; $y >= 2020; $y--)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="recurring_filter">Type</label>
                                    <select class="form-control" id="recurring_filter">
                                        <option value="">Tous les types</option>
                                        <option value="1">Récurrent (chaque année)</option>
                                        <option value="0">Ponctuel (année unique)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="working_day_filter">Régime</label>
                                    <select class="form-control" id="working_day_filter">
                                        <option value="">Tous</option>
                                        <option value="0">Non travaillé (chômé)</option>
                                        <option value="1">Travaillé</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group text-start">
                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success" id="create-holiday-button" data-bs-toggle="modal" data-bs-target="#createHolidayModal">
                                            <i class="bi bi-plus-circle me-1"></i> Nouveau jour férié
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="reset_filters">
                                            <i class="bi bi-x-circle me-1"></i> Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Récurrent</strong> : la date sera reconnue chaque année (ex : 01/01, 01/08). <strong>Non travaillé</strong> = les absences ce jour ne sont pas comptées comme faute et le jour est exclu des jours ouvrés dans les rapports.
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="holidays-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Nom</th>
                                        <th>Description</th>
                                        <th>Récurrence</th>
                                        <th>Régime</th>
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

<!-- Modal de création -->
<div class="modal fade" id="createHolidayModal" tabindex="-1" aria-labelledby="createHolidayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createHolidayModalLabel">Nouveau jour férié</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createHolidayForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="holiday_date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="holiday_date" name="holiday_date" required>
                        <div class="invalid-feedback" id="holiday_date-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required maxlength="255" placeholder="Ex : Fête de l'indépendance">
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" maxlength="1000" placeholder="Commentaire interne (optionnel)"></textarea>
                        <div class="invalid-feedback" id="description-error"></div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" value="1" checked>
                            <label class="form-check-label" for="is_recurring">Récurrent chaque année</label>
                        </div>
                        <div class="form-text">Décocher pour un férié ponctuel (une seule année).</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_working_day" name="is_working_day" value="1">
                            <label class="form-check-label" for="is_working_day">Jour travaillé (non chômé)</label>
                        </div>
                        <div class="form-text">Si coché, le jour reste compté comme ouvré malgré le férié.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-create-holiday">
                        <span id="create-holiday-text">Créer</span>
                        <span id="create-holiday-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'édition -->
<div class="modal fade" id="editHolidayModal" tabindex="-1" aria-labelledby="editHolidayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editHolidayModalLabel">Modifier le jour férié</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editHolidayForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_holiday_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_holiday_date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_holiday_date" name="holiday_date" required>
                        <div class="invalid-feedback" id="edit_holiday_date-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required maxlength="255">
                        <div class="invalid-feedback" id="edit_name-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2" maxlength="1000"></textarea>
                        <div class="invalid-feedback" id="edit_description-error"></div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_is_recurring" name="is_recurring" value="1">
                            <label class="form-check-label" for="edit_is_recurring">Récurrent chaque année</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_is_working_day" name="is_working_day" value="1">
                            <label class="form-check-label" for="edit_is_working_day">Jour travaillé</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-edit-holiday">
                        <span id="edit-holiday-text">Enregistrer</span>
                        <span id="edit-holiday-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteHolidayModal" tabindex="-1" aria-labelledby="deleteHolidayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteHolidayModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce jour férié ?</p>
                <p><strong>Nom :</strong> <span id="delete-holiday-name"></span></p>
                <p><strong>Date :</strong> <span id="delete-holiday-date"></span></p>
                <p class="text-danger"><small>Cette action est irréversible.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-holiday">
                    <span id="delete-holiday-text">Supprimer</span>
                    <span id="delete-holiday-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let holidayToDelete = null;
    let table;

    function initializeDataTable() {
        if ($.fn.DataTable.isDataTable('#holidays-table')) {
            $('#holidays-table').DataTable().destroy();
        }
        table = $('#holidays-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('holidays.datatable') }}",
                type: "GET",
                data: function(d) {
                    d.year_filter = $('#year_filter').val();
                    d.recurring_filter = $('#recurring_filter').val();
                    d.working_day_filter = $('#working_day_filter').val();
                }
            },
            columns: [
                { data: 'date_formatted', name: 'holiday_date', width: '18%' },
                { data: 'name', name: 'name', width: '25%' },
                { data: 'description', name: 'description', width: '25%', render: function(d){ return d || '<span class="text-muted">—</span>'; } },
                { data: 'recurring_badge', name: 'is_recurring', width: '14%', orderable: true, searchable: false },
                { data: 'working_day_badge', name: 'is_working_day', width: '13%', orderable: true, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '10%' }
            ],
            language: { url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json" },
            pageLength: 25,
            order: [[0, 'asc']],
            responsive: true
        });
    }

    initializeDataTable();

    $('#year_filter, #recurring_filter, #working_day_filter').on('change', function(){ table.ajax.reload(); });
    $('#reset_filters').on('click', function(){
        $('#year_filter').val('');
        $('#recurring_filter').val('');
        $('#working_day_filter').val('');
        table.ajax.reload();
    });

    // Création
    $('#createHolidayForm').on('submit', function(e){
        e.preventDefault();
        const btn = $('#submit-create-holiday');
        const sp = $('#create-holiday-spinner');
        const txt = $('#create-holiday-text');
        btn.prop('disabled', true); txt.addClass('d-none'); sp.removeClass('d-none');
        $('.invalid-feedback').text(''); $('.form-control').removeClass('is-invalid');
        // checkbox non coché -> 0
        let data = $(this).serializeArray();
        // serializeArray ne prend pas les unchecked, on force 0/1 via boolean
        let payload = {};
        data.forEach(function(item){ payload[item.name]=item.value; });
        payload.is_recurring = $('#is_recurring').is(':checked') ? 1 : 0;
        payload.is_working_day = $('#is_working_day').is(':checked') ? 1 : 0;
        payload._token = "{{ csrf_token() }}";
        $.ajax({
            url: "{{ route('holidays.store') }}",
            type: 'POST',
            data: payload,
            success: function(res){
                if(res.success){
                    $('#createHolidayModal').modal('hide');
                    $('#createHolidayForm')[0].reset();
                    $('#is_recurring').prop('checked', true);
                    table.ajax.reload();
                    Swal.fire({icon:'success', title:'Succès', text: res.message, timer: 2500, showConfirmButton:false});
                }
            },
            error: function(xhr){
                if(xhr.status===422){
                    const errors = xhr.responseJSON.errors || {};
                    Object.keys(errors).forEach(function(k){
                        const field = k==='holiday_date' ? 'holiday_date' : k;
                        $('#'+field).addClass('is-invalid');
                        $('#'+field+'-error').text(errors[k][0]);
                    });
                    if(xhr.responseJSON.message) Swal.fire({icon:'error', title:'Erreur', text: xhr.responseJSON.message});
                } else {
                    Swal.fire({icon:'error', title:'Erreur', text: 'Erreur serveur'});
                }
            },
            complete: function(){ btn.prop('disabled', false); txt.removeClass('d-none'); sp.addClass('d-none'); }
        });
    });

    // Édition - chargement
    $(document).on('click', '.edit-btn', function(){
        const id = $(this).data('id');
        $.get("{{ url('holidays') }}/"+id+"/edit", function(res){
            if(res.success){
                const d=res.data;
                $('#edit_holiday_id').val(d.id);
                $('#edit_holiday_date').val(d.holiday_date);
                $('#edit_name').val(d.name);
                $('#edit_description').val(d.description);
                $('#edit_is_recurring').prop('checked', !!d.is_recurring);
                $('#edit_is_working_day').prop('checked', !!d.is_working_day);
                $('#editHolidayModal').modal('show');
            }
        }).fail(function(){ Swal.fire({icon:'error', title:'Erreur', text: 'Chargement impossible'}); });
    });

    // Édition - submit
    $('#editHolidayForm').on('submit', function(e){
        e.preventDefault();
        const id = $('#edit_holiday_id').val();
        const btn = $('#submit-edit-holiday');
        const sp = $('#edit-holiday-spinner');
        const txt = $('#edit-holiday-text');
        btn.prop('disabled', true); txt.addClass('d-none'); sp.removeClass('d-none');
        $('.invalid-feedback').text(''); $('.form-control').removeClass('is-invalid');
        let payload = {};
        $(this).serializeArray().forEach(function(item){ payload[item.name]=item.value; });
        payload.is_recurring = $('#edit_is_recurring').is(':checked') ? 1 : 0;
        payload.is_working_day = $('#edit_is_working_day').is(':checked') ? 1 : 0;
        payload._token = "{{ csrf_token() }}";
        payload._method = 'PUT';
        $.ajax({
            url: "{{ url('holidays') }}/"+id,
            type: 'POST',
            data: payload,
            success: function(res){
                if(res.success){
                    $('#editHolidayModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({icon:'success', title:'Succès', text: res.message, timer: 2500, showConfirmButton:false});
                }
            },
            error: function(xhr){
                if(xhr.status===422){
                    const errors = xhr.responseJSON.errors || {};
                    Object.keys(errors).forEach(function(k){
                        const field = 'edit_'+k;
                        $('#'+field).addClass('is-invalid');
                        $('#'+field+'-error').text(errors[k][0]);
                    });
                    if(xhr.responseJSON.message) Swal.fire({icon:'error', title:'Erreur', text: xhr.responseJSON.message});
                } else {
                    Swal.fire({icon:'error', title:'Erreur', text: 'Erreur serveur'});
                }
            },
            complete: function(){ btn.prop('disabled', false); txt.removeClass('d-none'); sp.addClass('d-none'); }
        });
    });

    // Suppression
    $(document).on('click', '.delete-btn', function(){
        holidayToDelete = $(this).data('id');
        $('#delete-holiday-name').text($(this).data('name'));
        // récupérer date via ligne
        const row = table.row($(this).closest('tr')).data();
        $('#delete-holiday-date').text(row ? row.date_formatted : '');
        $('#deleteHolidayModal').modal('show');
    });
    $('#confirm-delete-holiday').on('click', function(){
        if(!holidayToDelete) return;
        const btn=$(this); const sp=$('#delete-holiday-spinner'); const txt=$('#delete-holiday-text');
        btn.prop('disabled', true); txt.addClass('d-none'); sp.removeClass('d-none');
        $.ajax({
            url: "{{ url('holidays') }}/"+holidayToDelete,
            type: 'POST',
            data: { _token: "{{ csrf_token() }}", _method: 'DELETE' },
            success: function(res){
                if(res.success){
                    $('#deleteHolidayModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({icon:'success', title:'Succès', text: res.message, timer: 2500, showConfirmButton:false});
                }
            },
            error: function(){ Swal.fire({icon:'error', title:'Erreur', text: 'Suppression impossible'}); },
            complete: function(){ btn.prop('disabled', false); txt.removeClass('d-none'); sp.addClass('d-none'); holidayToDelete=null; }
        });
    });

    // reset modals
    $('#createHolidayModal, #editHolidayModal').on('hidden.bs.modal', function(){
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
    });
});
</script>
<style>
    .badge { font-size: 0.75em; }
</style>
@endsection
