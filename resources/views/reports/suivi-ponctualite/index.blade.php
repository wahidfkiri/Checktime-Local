@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Tableau de Suivi de la Ponctualité</h3>
                        <p class="text-subtitle text-muted">
                            Retards, sorties et absences non justifiées, jour par jour
                        </p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Suivi de la ponctualité</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-header">
                        <div class="card bg-light mb-0">
                            <div class="card-body">
                                <h6 class="card-title">Filtres</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="suivi_start_date" class="form-label">Date début</label>
                                        <input type="date" class="form-control" id="suivi_start_date"
                                               value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="suivi_end_date" class="form-label">Date fin</label>
                                        <input type="date" class="form-control" id="suivi_end_date"
                                               value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="suivi_emp_code" class="form-label">Employé</label>
                                        <select class="form-control" id="suivi_emp_code">
                                            <option value="all">Tous les employés</option>
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee['emp_code'] }}">
                                                    {{ $employee['emp_code'] }} - {{ $employee['full_name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="suivi_department" class="form-label">Département</label>
                                        <select class="form-control" id="suivi_department">
                                            <option value="all">Tous les départements</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department }}">{{ $department }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-primary" id="suivi_generate">
                                                <i class="bi bi-table me-1"></i> Générer
                                            </button>
                                            <button type="button" class="btn btn-success" id="suivi_excel">
                                                <i class="bi bi-file-earmark-excel me-1"></i> Exporter Excel
                                            </button>
                                            <button type="button" class="btn btn-danger" id="suivi_pdf">
                                                <i class="bi bi-file-earmark-pdf me-1"></i> Exporter PDF
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info alert-sm p-2 mb-0 mt-3">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Seuls les jours ouvrés (lundi à vendredi) sont affichés. La période ne peut pas
                                    dépasser un mois. Pour une journée travaillée, la cellule indique l'heure
                                    d'arrivée et l'heure de départ, et en dessous l'anomalie éventuelle.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="suivi_placeholder" class="text-center text-muted py-5">
                            <i class="bi bi-table" style="font-size:2rem;"></i>
                            <p class="mt-2 mb-0">Choisissez une période puis cliquez sur « Générer ».</p>
                        </div>

                        <div id="suivi_result" class="d-none">
                            <div class="text-center mb-3">
                                <h5 class="mb-1">Tableau de Suivi de la Ponctualité</h5>
                                <div class="fw-bold" id="suivi_month"></div>
                                <div class="text-muted small" id="suivi_period"></div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm suivi-table mb-0">
                                    <thead id="suivi_thead"></thead>
                                    <tbody id="suivi_tbody"></tbody>
                                </table>
                            </div>

                            <div class="mt-3 small">
                                <span class="legende cell-ok">08:30 , 17:00</span> arrivée et départ
                                <span class="legende cell-late">25 mn</span> retard
                                <span class="legende cell-early">Incomplet</span> Incomplet
                                <span class="legende cell-absent">absent</span> absence non justifiée
                                <span class="legende cell-mission">en mission</span>
                                <span class="legende cell-leave">en congé</span>
                                <span class="legende cell-permission">autorisation</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<style>
    .suivi-table { font-size: 11px; }
    .suivi-table th,
    .suivi-table td { text-align: center; vertical-align: middle; }
    .suivi-table td { min-width: 62px; line-height: 1.25; }
    .suivi-table .cell-detail { display: block; font-size: 9px; color: #a15c00; }
    .suivi-table th.col-nom,
    .suivi-table td.col-nom { text-align: left; white-space: normal; min-width: 200px; font-weight: 600; }
    .suivi-table thead th { background-color: #f2f2f2; }

    .cell-late { background-color: #fff3cd; font-weight: 600; }
    .cell-early { background-color: #ffe5d0; }
    .cell-absent { background-color: #f8d7da; }
    .cell-mission { background-color: #d1ecf1; }
    .cell-leave { background-color: #d4edda; }
    .cell-permission { background-color: #e2e3e5; }
    .cell-ok { background-color: #ffffff; }
    .cell-total { background-color: #f2f2f2; font-weight: 700; }

    .legende {
        display: inline-block;
        padding: 1px 8px;
        margin: 0 4px 0 12px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    function filtres() {
        return {
            start_date: $('#suivi_start_date').val(),
            end_date: $('#suivi_end_date').val(),
            emp_code: $('#suivi_emp_code').val(),
            'department_ids[]': $('#suivi_department').val()
        };
    }

    function periodeValide() {
        var debut = $('#suivi_start_date').val();
        var fin = $('#suivi_end_date').val();

        if (!debut || !fin) {
            Swal.fire('Erreur', 'Veuillez sélectionner une période.', 'error');
            return false;
        }
        if (new Date(debut) > new Date(fin)) {
            Swal.fire('Erreur', 'La date de début ne peut pas être après la date de fin.', 'error');
            return false;
        }

        var jours = Math.round((new Date(fin) - new Date(debut)) / 86400000) + 1;
        if (jours > 31) {
            Swal.fire('Période trop longue', 'La période ne doit pas dépasser un mois (31 jours).', 'warning');
            return false;
        }
        return true;
    }

    function construireTableau(report) {
        // En-tête sur deux lignes : abréviation du jour, puis numéro du jour.
        var ligne1 = '<tr>'
            + '<th rowspan="2" class="col-nom">Nom et Prénoms</th>'
            + '<th colspan="' + report.days.length + '">JOURS OUVRABLES DE LA PÉRIODE</th>'
            + '<th colspan="2">TOTAL</th>'
            + '</tr><tr>';

        report.days.forEach(function (day) {
            ligne1 += '<th>' + day.day_short + '<br>' + day.day_number + '</th>';
        });
        ligne1 += '<th>Retard</th><th>en mn</th></tr>';
        $('#suivi_thead').html(ligne1);

        var corps = '';
        report.rows.forEach(function (row) {
            corps += '<tr><td class="col-nom">' + row.employee_name + '</td>';

            report.days.forEach(function (day) {
                var cell = row.cells[day.date] || { text: '', detail: '', type: 'ok' };
                var classe = ' class="cell-' + cell.type + '"';
                var contenu = cell.text || '';
                if (cell.detail) {
                    contenu += '<br><small class="cell-detail">' + cell.detail + '</small>';
                }
                corps += '<td' + classe + '>' + contenu + '</td>';
            });

            corps += '<td class="cell-total">' + row.total_retards + '</td>'
                   + '<td class="cell-total">' + row.total_minutes + '</td></tr>';
        });

        if (report.rows.length === 0) {
            corps = '<tr><td colspan="' + (report.days.length + 3) + '" class="text-center text-muted py-4">'
                  + 'Aucun employé pour ces critères.</td></tr>';
        } else {
            corps += '<tr class="cell-total"><td class="col-nom">TOTAL GÉNÉRAL</td>'
                   + '<td colspan="' + report.days.length + '"></td>'
                   + '<td>' + report.totals.retards + '</td>'
                   + '<td>' + report.totals.minutes + '</td></tr>';
        }

        $('#suivi_tbody').html(corps);
        $('#suivi_month').text(report.month_label);
        $('#suivi_period').text(report.period_label);
        $('#suivi_placeholder').addClass('d-none');
        $('#suivi_result').removeClass('d-none');
    }

    $('#suivi_generate').on('click', function () {
        if (!periodeValide()) return;

        var bouton = $(this);
        bouton.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Génération...');

        $.get("{{ route('reports.suivi-ponctualite.generate') }}", filtres())
            .done(function (response) {
                if (response.success) {
                    construireTableau(response.data);
                } else {
                    Swal.fire('Erreur', response.error || 'Erreur lors de la génération.', 'error');
                }
            })
            .fail(function (xhr) {
                Swal.fire('Erreur', (xhr.responseJSON && xhr.responseJSON.error) || 'Erreur serveur.', 'error');
            })
            .always(function () {
                bouton.prop('disabled', false).html('<i class="bi bi-table me-1"></i> Générer');
            });
    });

    $('#suivi_excel').on('click', function () {
        if (!periodeValide()) return;
        window.location.href = "{{ route('reports.suivi-ponctualite.export.excel') }}?" + $.param(filtres());
    });

    $('#suivi_pdf').on('click', function () {
        if (!periodeValide()) return;
        window.location.href = "{{ route('reports.suivi-ponctualite.export.pdf') }}?" + $.param(filtres());
    });
});
</script>
@endsection
