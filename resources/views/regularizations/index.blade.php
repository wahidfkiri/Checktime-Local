@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Régularisation de pointages</h3>
                        <p class="text-subtitle text-muted">Coupure d'électricité, oubli de pointage…</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Tableau de board</a>
                                </li>
                                <li class="breadcrumb-item active">Autre</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Régularisations récentes</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#regularizationModal">
                            <i class="bi bi-plus-circle me-1"></i> Nouvelle régularisation
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="regularizations-table">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Date</th>
                                        <th>Horaire</th>
                                        <th>Cause</th>
                                        <th>Enregistré le</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recent as $r)
                                        <tr>
                                            <td>
                                                {{ $r->employee ? $r->employee->full_name : '—' }}
                                                @if($r->employee)
                                                    <small class="text-muted">({{ $r->employee->emp_code }})</small>
                                                @endif
                                            </td>
                                            <td>{{ $r->date->format('d/m/Y') }}</td>
                                            <td>{{ substr($r->check_in, 0, 5) }} → {{ substr($r->check_out, 0, 5) }}</td>
                                            <td>{{ $r->cause ? $r->cause->label : '—' }}</td>
                                            <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Aucune régularisation enregistrée.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Modal de régularisation -->
<div class="modal fade" id="regularizationModal" tabindex="-1" aria-labelledby="regularizationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="regularizationModalLabel">Nouvelle régularisation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="regularizationForm">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label" for="cause_id">Cause <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-select" id="cause_id" name="cause_id">
                                    <option value="">Sélectionner une cause</option>
                                    @foreach($causes as $cause)
                                        <option value="{{ $cause->id }}">{{ $cause->label }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="add-cause-btn" title="Ajouter une cause">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback d-block text-danger" data-error-for="cause_id"></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="reg_date">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="reg_date" name="date" max="{{ now()->format('Y-m-d') }}">
                            <div class="text-danger small" data-error-for="date"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="reg_check_in">Heure d'arrivée <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="reg_check_in" name="check_in">
                            <div class="text-danger small" data-error-for="check_in"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="reg_check_out">Heure de départ <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="reg_check_out" name="check_out">
                            <div class="text-danger small" data-error-for="check_out"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Employés <span class="text-danger">*</span></label>
                        <div class="row g-2 mb-2 align-items-center">
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-sm" id="emp-filter" placeholder="Filtrer par code ou nom…" autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="emp-check-all">
                                    <label class="form-check-label" for="emp-check-all">Tout cocher</label>
                                </div>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <span class="badge bg-primary" id="emp-count">0 sélectionné(s)</span>
                            </div>
                        </div>
                        <div class="border rounded p-2" id="emp-list" style="max-height: 260px; overflow-y: auto;">
                            @foreach($employees as $employee)
                                <div class="form-check emp-item" data-search="{{ \Illuminate\Support\Str::lower($employee['emp_code'] . ' ' . $employee['full_name']) }}">
                                    <input class="form-check-input emp-checkbox" type="checkbox" value="{{ $employee['id'] }}" id="emp-{{ $employee['id'] }}">
                                    <label class="form-check-label w-100" for="emp-{{ $employee['id'] }}">
                                        <strong>{{ $employee['emp_code'] }}</strong> — {{ $employee['full_name'] }}
                                        <span class="emp-conflict-tag text-danger small d-none"> · pointage existant</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-danger small" data-error-for="employee_ids"></div>
                    </div>

                    <div class="alert alert-warning d-none" id="conflict-panel">
                        <strong><i class="bi bi-exclamation-triangle me-1"></i> Pointage déjà existant ce jour-là :</strong>
                        <ul class="mb-0 mt-1" id="conflict-list"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="reg-submit">
                        <span id="reg-submit-text">Enregistrer</span>
                        <span id="reg-submit-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    const csrf = "{{ csrf_token() }}";
    const routes = {
        check:     "{{ route('authorizations.regularizations.check') }}",
        store:     "{{ route('authorizations.regularizations.store') }}",
        causeStore: "{{ route('authorizations.regularizations.causes.store') }}"
    };
    const employeeNames = @json(collect($employees)->pluck('full_name', 'id'));

    let conflicts = {};
    let checkTimer = null;

    function selectedIds() {
        return $('.emp-checkbox:checked').map(function () { return parseInt(this.value, 10); }).get();
    }

    function updateCount() {
        $('#emp-count').text(selectedIds().length + ' sélectionné(s)');
    }

    function clearErrors() {
        $('[data-error-for]').text('');
    }

    function showErrors(errors) {
        Object.keys(errors || {}).forEach(function (field) {
            const key = field.replace(/\.\d+$/, '');
            $('[data-error-for="' + key + '"]').text(errors[field][0]);
        });
    }

    function renderConflicts() {
        $('.emp-conflict-tag').addClass('d-none');
        $('.emp-item label').removeClass('text-danger');
        $('#conflict-list').empty();

        const ids = Object.keys(conflicts);
        ids.forEach(function (id) {
            const item = $('#emp-' + id).closest('.emp-item');
            item.find('.emp-conflict-tag').removeClass('d-none');
            item.find('label').addClass('text-danger');

            const c = conflicts[id];
            const hours = (c.check_in || '--:--') + ' → ' + (c.check_out || '--:--');
            $('#conflict-list').append($('<li>').text((employeeNames[id] || ('#' + id)) + ' : ' + hours + ' (' + c.status + ')'));
        });

        $('#conflict-panel').toggleClass('d-none', ids.length === 0);
    }

    function runCheck() {
        const date = $('#reg_date').val();
        const ids = selectedIds();

        if (!date || ids.length === 0) {
            conflicts = {};
            renderConflicts();
            return;
        }

        $.post(routes.check, { _token: csrf, date: date, employee_ids: ids }, function (data) {
            conflicts = data || {};
            renderConflicts();
        });
    }

    function scheduleCheck() {
        clearTimeout(checkTimer);
        checkTimer = setTimeout(runCheck, 250);
    }

    // Filtre code / nom
    $('#emp-filter').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        $('.emp-item').each(function () {
            $(this).toggle(q === '' || $(this).data('search').indexOf(q) !== -1);
        });
    });

    // Tout cocher (employés visibles uniquement)
    $('#emp-check-all').on('change', function () {
        const checked = this.checked;
        $('.emp-item:visible .emp-checkbox').prop('checked', checked);
        updateCount();
        scheduleCheck();
    });

    $(document).on('change', '.emp-checkbox', function () {
        updateCount();
        scheduleCheck();
    });

    $('#reg_date').on('change', scheduleCheck);

    // Bouton « + » : nouvelle cause
    $('#add-cause-btn').on('click', function () {
        Swal.fire({
            title: 'Nouvelle cause',
            input: 'text',
            inputLabel: 'Libellé de la cause',
            inputPlaceholder: 'Ex. : Panne de la pointeuse',
            showCancelButton: true,
            confirmButtonText: 'Ajouter',
            cancelButtonText: 'Annuler',
            inputValidator: function (value) {
                if (!value || !value.trim()) return 'Veuillez saisir un libellé.';
            }
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.post(routes.causeStore, { _token: csrf, label: result.value })
                .done(function (cause) {
                    $('#cause_id').append($('<option>').val(cause.id).text(cause.label)).val(cause.id);
                    $('[data-error-for="cause_id"]').text('');
                })
                .fail(function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Erreur lors de l\'ajout de la cause.';
                    Swal.fire({ icon: 'error', title: 'Erreur', text: msg });
                });
        });
    });

    function submitForm(force) {
        clearErrors();
        $('#reg-submit').prop('disabled', true);
        $('#reg-submit-spinner').removeClass('d-none');

        $.post(routes.store, {
            _token: csrf,
            cause_id: $('#cause_id').val(),
            date: $('#reg_date').val(),
            check_in: $('#reg_check_in').val(),
            check_out: $('#reg_check_out').val(),
            employee_ids: selectedIds(),
            force: force ? 1 : 0
        })
        .done(function (res) {
            Swal.fire({ icon: res.applied > 0 ? 'success' : 'info', title: 'Terminé', text: res.message })
                .then(function () { window.location.reload(); });
        })
        .fail(function (xhr) {
            if (xhr.status === 422) {
                showErrors(xhr.responseJSON && xhr.responseJSON.errors);
            } else {
                Swal.fire({ icon: 'error', title: 'Erreur', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Une erreur est survenue.' });
            }
        })
        .always(function () {
            $('#reg-submit').prop('disabled', false);
            $('#reg-submit-spinner').addClass('d-none');
        });
    }

    $('#regularizationForm').on('submit', function (e) {
        e.preventDefault();

        const conflictedSelected = selectedIds().filter(function (id) { return conflicts[id]; });

        if (conflictedSelected.length === 0) {
            submitForm(false);
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Pointages existants',
            html: conflictedSelected.length + ' employé(s) sélectionné(s) ont déjà un pointage ce jour-là.<br>Que faire ?',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Écraser',
            denyButtonText: 'Ignorer ces employés',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (result.isConfirmed) submitForm(true);
            else if (result.isDenied) submitForm(false);
        });
    });

    $('#regularizationModal').on('hidden.bs.modal', function () {
        $('#regularizationForm')[0].reset();
        conflicts = {};
        renderConflicts();
        clearErrors();
        $('.emp-item').show();
        updateCount();
    });
});
</script>
@endsection
