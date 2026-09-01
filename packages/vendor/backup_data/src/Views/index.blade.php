@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Sauvegarde des données</h3>
                        <p class="text-subtitle text-muted">Exporter la base de données (SQL + CSV) et consulter l'historique</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Sauvegarde des données</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Carte : générer une sauvegarde -->
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-1"><i class="bi bi-database-down me-1"></i> Générer une sauvegarde complète</h5>
                                <p class="text-muted mb-0">
                                    Crée un fichier <strong>.zip</strong> contenant&nbsp;:
                                    <span class="badge bg-secondary">database.sql</span>
                                    (structure + données) et un dossier
                                    <span class="badge bg-secondary">csv/</span>
                                    avec un fichier CSV par table. La génération peut prendre un moment selon la taille de la base.
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <form action="{{ route('backup-data.export') }}" method="POST" id="exportForm">
                                    @csrf
                                    <button type="button" class="btn btn-primary" id="btn-open-export">
                                        <i class="bi bi-download me-1"></i> Exporter la base
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte : historique -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0"><i class="bi bi-clock-history me-1"></i> Historique des exports</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Fichier</th>
                                        <th class="text-center">Taille</th>
                                        <th class="text-center">Tables</th>
                                        <th class="text-center">Lignes</th>
                                        <th class="text-center">Statut</th>
                                        <th>Par</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($backups as $backup)
                                        <tr>
                                            <td>{{ $backup->created_at?->format('d/m/Y H:i') }}</td>
                                            <td><span class="font-monospace small">{{ $backup->filename }}</span></td>
                                            <td class="text-center">{{ $backup->size_human }}</td>
                                            <td class="text-center">{{ $backup->tables_count }}</td>
                                            <td class="text-center">{{ number_format($backup->rows_count, 0, ',', ' ') }}</td>
                                            <td class="text-center">
                                                @if($backup->status === 'completed')
                                                    <span class="badge bg-success">Réussi</span>
                                                @else
                                                    <span class="badge bg-danger" title="{{ $backup->error }}">Échoué</span>
                                                @endif
                                            </td>
                                            <td>{{ $backup->created_by_name ?? '—' }}</td>
                                            <td class="text-end">
                                                @if($backup->status === 'completed')
                                                    <a href="{{ route('backup-data.download', $backup) }}"
                                                       class="btn btn-sm btn-success" title="Télécharger">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                @endif
                                                <form action="{{ route('backup-data.destroy', $backup) }}" method="POST"
                                                      class="d-inline" onsubmit="return confirm('Supprimer cette sauvegarde ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox me-1"></i> Aucune sauvegarde pour le moment.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $backups->links() }}
                        </div>
                    </div>
                </div>

            </section>
        </div>
    </div>
</div>

<!-- Modal de confirmation d'export -->
<div class="modal fade" id="exportConfirmModal" tabindex="-1" aria-labelledby="exportConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportConfirmLabel">
                    <i class="bi bi-database-down me-1"></i> Confirmer l'export
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Voulez-vous générer une <strong>sauvegarde complète</strong> de la base de données ?</p>
                <p class="text-muted mb-0 small">
                    Un fichier <strong>.zip</strong> sera créé (<code>database.sql</code> + dossier <code>csv/</code>).
                    Selon la taille de la base, l'opération peut durer un moment.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-export">
                    <i class="bi bi-download me-1"></i> Confirmer l'export
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de progression (non fermable) -->
<div class="modal fade" id="exportProgressModal" tabindex="-1" aria-labelledby="exportProgressLabel"
     data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Chargement…</span>
                </div>
                <h5 class="mb-3">Génération de la sauvegarde…</h5>
                <div class="progress" style="height: 22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                         id="export-progress-bar" role="progressbar"
                         style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <p class="text-muted small mt-3 mb-0" id="export-progress-text">Initialisation…</p>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var confirmModalEl  = document.getElementById('exportConfirmModal');
        var progressModalEl = document.getElementById('exportProgressModal');

        // Ouverture/fermeture compatibles Bootstrap 5 natif ou plugin jQuery.
        function showModal(el) {
            if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(el).show(); }
            else if (window.jQuery && jQuery.fn.modal) { jQuery(el).modal('show'); }
        }
        function hideModal(el) {
            if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(el).hide(); }
            else if (window.jQuery && jQuery.fn.modal) { jQuery(el).modal('hide'); }
        }

        var bar  = document.getElementById('export-progress-bar');
        var text = document.getElementById('export-progress-text');

        var steps = [
            { p: 10,  m: 'Lecture des tables…' },
            { p: 30,  m: 'Export de la structure (SQL)…' },
            { p: 55,  m: 'Export des données…' },
            { p: 75,  m: 'Génération des fichiers CSV…' },
            { p: 90,  m: 'Compression de l\'archive .zip…' }
        ];
        var timer = null;

        function setProgress(percent, message) {
            var v = Math.min(Math.round(percent), 100);
            bar.style.width = v + '%';
            bar.setAttribute('aria-valuenow', v);
            bar.textContent = v + '%';
            if (message) { text.textContent = message; }
        }

        function startSimulatedProgress() {
            var i = 0;
            setProgress(3, 'Initialisation…');
            timer = setInterval(function () {
                if (i < steps.length) {
                    setProgress(steps[i].p, steps[i].m);
                    i++;
                } else {
                    // Palier : on approche 95% sans jamais atteindre 100%
                    // (100% n'arrive qu'au retour serveur = rechargement de page).
                    var current = parseInt(bar.getAttribute('aria-valuenow'), 10) || 90;
                    if (current < 95) { setProgress(current + 1, 'Finalisation…'); }
                }
            }, 800);
        }

        // Ouvrir la confirmation
        document.getElementById('btn-open-export').addEventListener('click', function () {
            showModal(confirmModalEl);
        });

        // Confirmer -> progression + soumission du formulaire (POST plein page)
        document.getElementById('btn-confirm-export').addEventListener('click', function () {
            hideModal(confirmModalEl);
            showModal(progressModalEl);
            startSimulatedProgress();
            // Laisse le temps à la modal de s'afficher avant de bloquer sur la requête
            setTimeout(function () {
                document.getElementById('exportForm').submit();
            }, 300);
        });

        // Sécurité : stopper l'animation si l'utilisateur quitte la page
        window.addEventListener('pagehide', function () {
            if (timer) { clearInterval(timer); }
        });
    })();
</script>
@endsection
