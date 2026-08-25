@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Modèles d'édition PDF</h3>
                        <p class="text-subtitle text-muted">Colonnes affichées sur l'export du rapport Présence &amp; Ponctualité</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Paramètres</a></li>
                                <li class="breadcrumb-item active">Modèles d'édition</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="row">
                    <!-- Liste des modèles -->
                    <div class="col-12 col-lg-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Modèles enregistrés</h6>
                                <button type="button" class="btn btn-sm btn-success" id="new-template-button">
                                    <i class="bi bi-plus-circle me-1"></i> Nouveau
                                </button>
                            </div>
                            <div class="list-group list-group-flush" id="templates-list">
                                @foreach($templates as $template)
                                    <div class="list-group-item template-item" data-id="{{ $template->id }}" style="cursor:pointer;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-bold">
                                                    {{ $template->name }}
                                                    @if($template->is_default)
                                                        <span class="badge bg-success ms-1">Par défaut</span>
                                                    @endif
                                                </div>
                                                @if($template->description)
                                                    <small class="text-muted">{{ $template->description }}</small><br>
                                                @endif
                                                <small class="text-muted">{{ count($template->resolvedColumns()) }} colonne(s)</small>
                                            </div>
                                        </div>
                                        <div class="btn-group btn-group-sm mt-2" role="group">
                                            <button type="button" class="btn btn-outline-primary edit-template-btn" title="Modifier"><i class="bi bi-pencil"></i></button>
                                            <button type="button" class="btn btn-outline-secondary duplicate-template-btn" title="Dupliquer"><i class="bi bi-copy"></i></button>
                                            <button type="button" class="btn btn-outline-success set-default-btn" title="Définir par défaut" @if($template->is_default) disabled @endif><i class="bi bi-star"></i></button>
                                            <button type="button" class="btn btn-outline-danger delete-template-btn" title="Supprimer"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Éditeur de modèle -->
                    <div class="col-12 col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0" id="editor-title">Nouveau modèle</h6>
                            </div>
                            <div class="card-body">
                                <form id="templateForm">
                                    <input type="hidden" id="template_id" value="">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label for="template_name" class="form-label">Nom du modèle <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="template_name" maxlength="150" required>
                                            <div class="invalid-feedback" id="name-error"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="template_description" class="form-label">Description</label>
                                            <input type="text" class="form-control" id="template_description" maxlength="255">
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4">
                                            <label for="option_orientation" class="form-label">Orientation du PDF</label>
                                            <select class="form-control" id="option_orientation">
                                                <option value="landscape">Paysage</option>
                                                <option value="portrait">Portrait</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="option_edition" class="form-label">Type d'édition</label>
                                            <select class="form-control" id="option_edition">
                                                <option value="standard">Résumé standard (1 ligne par employé)</option>
                                                <option value="department">Détail par département (pointages jour par jour)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="option_layout" class="form-label">Disposition</label>
                                            <select class="form-control" id="option_layout">
                                                <option value="single">Tableau unique</option>
                                                <option value="per_section">Un tableau par section (Présence, Ponctualité...)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="option_show_totals" checked>
                                                <label class="form-check-label" for="option_show_totals">Ligne de totaux</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="option_show_signatures" checked>
                                                <label class="form-check-label" for="option_show_signatures">Signature</label>
                                            </div>
                                        </div>
                                    </div>

                                    <label class="form-label">Colonnes à inclure dans le PDF <span class="text-danger">*</span></label>
                                    <div id="columns-error" class="text-danger small mb-2"></div>

                                    @foreach($groups as $groupKey => $group)
                                        <div class="card bg-light mb-2">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input group-check" type="checkbox" id="group_{{ $groupKey }}" data-group="{{ $groupKey }}">
                                                        <label class="form-check-label fw-bold" for="group_{{ $groupKey }}">{{ $group['label'] }}</label>
                                                    </div>
                                                    <span class="badge bg-secondary group-count" data-group="{{ $groupKey }}">0/{{ count($group['columns']) }}</span>
                                                </div>
                                                <div class="row">
                                                    @foreach($group['columns'] as $colKey => $col)
                                                        <div class="col-md-4 col-6">
                                                            <div class="form-check">
                                                                <input class="form-check-input column-check" type="checkbox" value="{{ $colKey }}" data-group="{{ $groupKey }}" id="col_{{ $colKey }}">
                                                                <label class="form-check-label" for="col_{{ $colKey }}">{{ $col['label'] }}</label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-1">Aperçu de l'ordre des colonnes sur le PDF :</small>
                                        <div id="columns-preview"><span class="text-muted small">Aucune colonne sélectionnée</span></div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="template_is_default">
                                        <label class="form-check-label" for="template_is_default">Définir comme modèle par défaut</label>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" id="cancel-edit-button">Annuler</button>
                                        <button type="submit" class="btn btn-primary" id="submit-template">
                                            <span id="template-submit-text">Enregistrer</span>
                                            <span id="template-submit-spinner" class="spinner-border spinner-border-sm d-none"></span>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Catalogue des colonnes (clé => libellé), dans l'ordre du catalogue serveur —
    // sert à construire l'aperçu de l'ordre et à trier les colonnes cochées.
    var CATALOGUE_ORDER = [
        @foreach($groups as $group)
            @foreach($group['columns'] as $colKey => $col)
                { key: "{{ $colKey }}", label: "{{ $col['label'] }}" },
            @endforeach
        @endforeach
    ];

    var templatesData = {}; // rempli via /list, indexé par id
    var isSubmitting = false;

    function csrfHeaders() {
        return { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') };
    }

    function showSuccess(message) {
        Swal.fire({ icon: 'success', title: 'Succès', text: message, timer: 3000, showConfirmButton: false });
    }

    function showError(message) {
        Swal.fire({ icon: 'error', title: 'Erreur', text: message });
    }

    // ========== ÉTAT DES CASES À COCHER ==========

    function colonnesCochees() {
        return CATALOGUE_ORDER.filter(function(c) { return $('#col_' + c.key).is(':checked'); })
                               .map(function(c) { return c.key; });
    }

    function cocherColonnes(keys) {
        $('.column-check').prop('checked', false);
        (keys || []).forEach(function(key) {
            $('#col_' + key).prop('checked', true);
        });
        rafraichirEtat();
    }

    function rafraichirEtat() {
        // État (tout/partiel/aucun) de la case "groupe" + compteur
        $('.group-check').each(function() {
            var group = $(this).data('group');
            var $cols = $('.column-check[data-group="' + group + '"]');
            var total = $cols.length;
            var checked = $cols.filter(':checked').length;

            $(this).prop('checked', checked === total && total > 0);
            $(this).prop('indeterminate', checked > 0 && checked < total);
            $('.group-count[data-group="' + group + '"]').text(checked + '/' + total);
        });

        // Aperçu de l'ordre des colonnes (ordre catalogue, pas ordre de clic)
        var selected = colonnesCochees();
        if (selected.length === 0) {
            $('#columns-preview').html('<span class="text-muted small">Aucune colonne sélectionnée</span>');
        } else {
            var badges = selected.map(function(key) {
                var col = CATALOGUE_ORDER.find(function(c) { return c.key === key; });
                return '<span class="badge bg-primary me-1 mb-1">' + (col ? col.label : key) + '</span>';
            });
            $('#columns-preview').html(badges.join(''));
        }
    }

    $('.group-check').on('change', function() {
        var group = $(this).data('group');
        var checked = $(this).is(':checked');
        $('.column-check[data-group="' + group + '"]').prop('checked', checked);
        rafraichirEtat();
    });

    $('.column-check').on('change', rafraichirEtat);

    // ========== FORMULAIRE (nouveau / édition) ==========

    function resetForm() {
        $('#template_id').val('');
        $('#template_name').val('');
        $('#template_description').val('');
        $('#option_orientation').val('landscape');
        $('#option_edition').val('standard');
        $('#option_layout').val('single');
        $('#option_show_totals').prop('checked', true);
        $('#option_show_signatures').prop('checked', true);
        $('#template_is_default').prop('checked', false);
        cocherColonnes(@json(\App\Reports\PresencePonctualiteColumns::defaultKeys()));
        $('#editor-title').text('Nouveau modèle');
        $('.is-invalid').removeClass('is-invalid');
        $('#name-error, #columns-error').text('');
        $('.template-item').removeClass('active bg-light');
    }

    function chargerDansFormulaire(template) {
        $('#template_id').val(template.id);
        $('#template_name').val(template.name);
        $('#template_description').val(template.description || '');
        $('#option_orientation').val(template.options.orientation || 'landscape');
        $('#option_edition').val(template.options.edition || 'standard');
        $('#option_layout').val(template.options.layout || 'single');
        $('#option_show_totals').prop('checked', !!template.options.show_totals);
        $('#option_show_signatures').prop('checked', !!template.options.show_signatures);
        $('#template_is_default').prop('checked', !!template.is_default);
        cocherColonnes(template.columns);
        $('#editor-title').text('Modifier : ' + template.name);
        $('.is-invalid').removeClass('is-invalid');
        $('#name-error, #columns-error').text('');
    }

    $('#new-template-button, #cancel-edit-button').on('click', function() {
        resetForm();
    });

    // ========== LISTE : ouvrir un modèle dans l'éditeur ==========

    $(document).on('click', '.template-item', function(e) {
        if ($(e.target).closest('.btn-group').length) return; // clic sur un bouton d'action
        var id = $(this).data('id');
        $.get("{{ route('settings.report-templates.list') }}", function(response) {
            var template = response.templates.find(function(t) { return t.id === id; });
            if (template) {
                templatesData[id] = template;
                chargerDansFormulaire(template);
                $('.template-item').removeClass('active bg-light');
                $('[data-id="' + id + '"]').addClass('bg-light');
            }
        });
    });

    // ========== ENREGISTRER (créer / modifier) ==========

    $('#templateForm').on('submit', function(e) {
        e.preventDefault();
        if (isSubmitting) return;

        var columns = colonnesCochees();
        $('.is-invalid').removeClass('is-invalid');
        $('#name-error, #columns-error').text('');

        if (columns.length === 0) {
            $('#columns-error').text('Cochez au moins une colonne.');
            return;
        }

        var id = $('#template_id').val();
        var payload = {
            _token: "{{ csrf_token() }}",
            name: $('#template_name').val(),
            description: $('#template_description').val(),
            columns: columns,
            options: {
                orientation: $('#option_orientation').val(),
                edition: $('#option_edition').val(),
                layout: $('#option_layout').val(),
                show_totals: $('#option_show_totals').is(':checked') ? 1 : 0,
                show_signatures: $('#option_show_signatures').is(':checked') ? 1 : 0,
            },
            is_default: $('#template_is_default').is(':checked') ? 1 : 0,
        };

        var url = id ? "{{ url('settings/modeles-rapport') }}/" + id : "{{ route('settings.report-templates.store') }}";
        if (id) payload._method = 'PUT';

        isSubmitting = true;
        $('#submit-template').prop('disabled', true);
        $('#template-submit-text').addClass('d-none');
        $('#template-submit-spinner').removeClass('d-none');

        $.ajax({
            url: url,
            type: 'POST',
            data: payload,
            headers: csrfHeaders(),
            success: function(response) {
                showSuccess(response.message);
                recharger(response.template.id);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors || {};
                    if (errors.name) { $('#template_name').addClass('is-invalid'); $('#name-error').text(errors.name[0]); }
                    if (errors['columns'] || (xhr.responseJSON.message && !Object.keys(errors).length)) {
                        $('#columns-error').text(xhr.responseJSON.message || (errors['columns'] ? errors['columns'][0] : ''));
                    }
                } else {
                    showError(xhr.responseJSON?.message || "Erreur lors de l'enregistrement du modèle.");
                }
            },
            complete: function() {
                isSubmitting = false;
                $('#submit-template').prop('disabled', false);
                $('#template-submit-text').removeClass('d-none');
                $('#template-submit-spinner').addClass('d-none');
            }
        });
    });

    // ========== DUPLIQUER / DÉFAUT / SUPPRIMER ==========

    $(document).on('click', '.duplicate-template-btn', function(e) {
        e.stopPropagation();
        var id = $(this).closest('.template-item').data('id');
        $.ajax({
            url: "{{ url('settings/modeles-rapport') }}/" + id + '/duplicate',
            type: 'POST',
            data: { _token: "{{ csrf_token() }}" },
            success: function(response) { showSuccess(response.message); recharger(); },
            error: function(xhr) { showError(xhr.responseJSON?.message || 'Erreur lors de la duplication.'); }
        });
    });

    $(document).on('click', '.set-default-btn', function(e) {
        e.stopPropagation();
        var id = $(this).closest('.template-item').data('id');
        $.ajax({
            url: "{{ url('settings/modeles-rapport') }}/" + id + '/default',
            type: 'POST',
            data: { _token: "{{ csrf_token() }}" },
            success: function(response) { showSuccess(response.message); recharger(); },
            error: function(xhr) { showError(xhr.responseJSON?.message || 'Erreur.'); }
        });
    });

    $(document).on('click', '.delete-template-btn', function(e) {
        e.stopPropagation();
        var $item = $(this).closest('.template-item');
        var id = $item.data('id');
        var name = $item.find('.fw-bold').text().trim();

        Swal.fire({
            icon: 'warning',
            title: 'Supprimer ce modèle ?',
            text: name + ' — cette action est irréversible.',
            showCancelButton: true,
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#dc3545',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('settings/modeles-rapport') }}/" + id,
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", _method: 'DELETE' },
                success: function(response) { showSuccess(response.message); resetForm(); recharger(); },
                error: function(xhr) { showError(xhr.responseJSON?.message || 'Erreur lors de la suppression.'); }
            });
        });
    });

    $(document).on('click', '.edit-template-btn', function(e) {
        e.stopPropagation();
        $(this).closest('.template-item').trigger('click');
    });

    // ========== RECHARGER LA LISTE (sans recharger la page) ==========

    function recharger(selectId) {
        $.get("{{ route('settings.report-templates.list') }}", function(response) {
            var $list = $('#templates-list');
            $list.empty();

            response.templates.forEach(function(t) {
                templatesData[t.id] = t;
                var defaultBadge = t.is_default ? ' <span class="badge bg-success ms-1">Par défaut</span>' : '';
                var description = t.description ? '<small class="text-muted">' + t.description + '</small><br>' : '';
                var disabled = t.is_default ? 'disabled' : '';

                var html =
                    '<div class="list-group-item template-item" data-id="' + t.id + '" style="cursor:pointer;">' +
                        '<div class="d-flex justify-content-between align-items-start">' +
                            '<div>' +
                                '<div class="fw-bold">' + t.name + defaultBadge + '</div>' +
                                description +
                                '<small class="text-muted">' + t.column_count + ' colonne(s)</small>' +
                            '</div>' +
                        '</div>' +
                        '<div class="btn-group btn-group-sm mt-2" role="group">' +
                            '<button type="button" class="btn btn-outline-primary edit-template-btn" title="Modifier"><i class="bi bi-pencil"></i></button>' +
                            '<button type="button" class="btn btn-outline-secondary duplicate-template-btn" title="Dupliquer"><i class="bi bi-copy"></i></button>' +
                            '<button type="button" class="btn btn-outline-success set-default-btn" title="Définir par défaut" ' + disabled + '><i class="bi bi-star"></i></button>' +
                            '<button type="button" class="btn btn-outline-danger delete-template-btn" title="Supprimer"><i class="bi bi-trash"></i></button>' +
                        '</div>' +
                    '</div>';

                $list.append(html);
            });

            if (selectId) {
                var t = response.templates.find(function(x) { return x.id === selectId; });
                if (t) {
                    chargerDansFormulaire(t);
                    $('[data-id="' + selectId + '"]').addClass('bg-light');
                }
            }
        });
    }

    // Initialisation
    resetForm();
});
</script>
@endsection
