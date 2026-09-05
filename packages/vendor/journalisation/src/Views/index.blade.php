@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Journal des activités</h3>
                        <p class="text-subtitle text-muted">Suivi des actions des utilisateurs (connexions, créations, modifications, suppressions, exports…)</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Journal des activités</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                @php $qs = request()->query(); @endphp

                <!-- Filtres -->
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('journalisation.index') }}">
                            <div class="row g-3">
                                <div class="col-6 col-md-3 col-lg-2">
                                    <label class="form-label">Utilisateur</label>
                                    <select name="user_id" class="form-control">
                                        <option value="">Tous</option>
                                        @foreach($users as $id => $name)
                                            <option value="{{ $id }}" @selected((string)($filters['user_id'] ?? '') === (string)$id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <label class="form-label">Action</label>
                                    <select name="action" class="form-control">
                                        <option value="">Toutes</option>
                                        @foreach($actions as $key => $label)
                                            <option value="{{ $key }}" @selected(($filters['action'] ?? '') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <label class="form-label">Du</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                                </div>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <label class="form-label">Au</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                                </div>
                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label">Recherche</label>
                                    <input type="text" name="search" class="form-control" placeholder="Description, utilisateur…" value="{{ $filters['search'] ?? '' }}">
                                </div>
                                <div class="col-12 col-lg-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('journalisation.index') }}" class="btn btn-sm btn-light">
                                    <i class="bi bi-x-circle me-1"></i> Réinitialiser
                                </a>
                                <a href="{{ route('journalisation.export.excel', $qs) }}" class="btn btn-sm btn-success">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                                </a>
                                <a href="{{ route('journalisation.export.pdf', $qs) }}" class="btn btn-sm btn-danger">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tableau -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Date &amp; heure</th>
                                        <th>Utilisateur</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($logs as $log)
                                        <tr>
                                            <td class="text-nowrap">{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                                            <td>{{ $log->user_name ?? '—' }}</td>
                                            <td><span class="badge bg-{{ $log->action_color }}">{{ $log->action_label }}</span></td>
                                            <td class="small">{{ $log->description }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox me-1"></i> Aucune activité enregistrée pour ces critères.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                            <div class="text-muted small">
                                @if($logs->total() > 0)
                                    Affichage de {{ $logs->firstItem() }} à {{ $logs->lastItem() }}
                                    sur {{ number_format($logs->total(), 0, ',', ' ') }} activité(s)
                                @else
                                    Aucune activité
                                @endif
                            </div>
                            <div class="journal-pagination">
                                {{ $logs->onEachSide(1)->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<style>
    /* Pagination alignée sur le thème Bootstrap 5, sans marge résiduelle */
    .journal-pagination .pagination { margin-bottom: 0; }
    .journal-pagination nav { display: flex; justify-content: flex-end; }
</style>
@endsection
