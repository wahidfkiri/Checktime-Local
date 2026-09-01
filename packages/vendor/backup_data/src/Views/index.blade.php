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
                                <form action="{{ route('backup-data.export') }}" method="POST"
                                      onsubmit="return confirmExport(this);">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" id="btn-export">
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

<script>
    function confirmExport(form) {
        if (!confirm('Générer une sauvegarde complète de la base de données ?')) {
            return false;
        }
        var btn = form.querySelector('#btn-export');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Génération en cours...';
        }
        return true;
    }
</script>
@endsection
