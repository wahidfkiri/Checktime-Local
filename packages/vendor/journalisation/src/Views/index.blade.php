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
                            <table class="table table-hover table-sm align-middle journal-table">
                                <thead>
                                    <tr>
                                        <th style="width:1%"></th>
                                        <th>Date &amp; heure</th>
                                        <th>Utilisateur</th>
                                        <th>Action</th>
                                        <th>Élément concerné</th>
                                        <th>Description</th>
                                        <th>Détails</th>
                                        <th>Origine</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($logs as $log)
                                        @php $detailId = 'journal-detail-' . $log->id; @endphp
                                        <tr>
                                            <td class="text-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-link p-0 journal-toggle collapsed"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#{{ $detailId }}"
                                                        aria-expanded="false"
                                                        aria-controls="{{ $detailId }}"
                                                        title="Afficher le détail technique">
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                            </td>
                                            <td class="text-nowrap">
                                                {{ optional($log->created_at)->format('d/m/Y H:i:s') }}
                                                <div class="text-muted" style="font-size:.72rem">
                                                    {{ optional($log->created_at)->diffForHumans() }}
                                                </div>
                                            </td>
                                            <td>
                                                {{ $log->user_name ?? '—' }}
                                                @if($log->user_id)
                                                    <div class="text-muted" style="font-size:.72rem">#{{ $log->user_id }}</div>
                                                @endif
                                            </td>
                                            <td class="text-nowrap">
                                                <span class="badge bg-{{ $log->action_color }}">
                                                    <i class="bi {{ $log->action_icon }} me-1"></i>{{ $log->action_label }}
                                                </span>
                                            </td>
                                            <td class="small">
                                                @if($log->subject_label)
                                                    <span class="badge bg-light text-dark border">{{ $log->subject_label }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="small">{{ $log->description ?: '—' }}</td>
                                            <td class="small journal-details">
                                                @if($log->details_summary)
                                                    {{ $log->details_summary }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="small text-nowrap">
                                                {{ $log->ip_address ?: '—' }}
                                                @if($log->device_label)
                                                    <div class="text-muted" style="font-size:.72rem">{{ $log->device_label }}</div>
                                                @endif
                                            </td>
                                        </tr>

                                        {{-- Détail technique complet, replié par défaut --}}
                                        <tr class="collapse journal-detail-row" id="{{ $detailId }}">
                                            <td colspan="8" class="bg-light">
                                                <div class="row g-3 py-2 px-1 small">
                                                    <div class="col-12 col-lg-6">
                                                        <div class="fw-bold text-muted text-uppercase mb-2" style="font-size:.7rem">Contexte de la requête</div>
                                                        <dl class="row mb-0 journal-dl">
                                                            <dt class="col-4">Méthode</dt>
                                                            <dd class="col-8">{{ $log->method ?: '—' }}</dd>

                                                            <dt class="col-4">Route</dt>
                                                            <dd class="col-8">{{ $log->route ?: '—' }}</dd>

                                                            <dt class="col-4">URL</dt>
                                                            <dd class="col-8 text-break">{{ $log->url_path ?: '—' }}</dd>

                                                            <dt class="col-4">Adresse IP</dt>
                                                            <dd class="col-8">{{ $log->ip_address ?: '—' }}</dd>

                                                            <dt class="col-4">Navigateur</dt>
                                                            <dd class="col-8">{{ $log->device_label ?: '—' }}</dd>

                                                            <dt class="col-4">Objet</dt>
                                                            <dd class="col-8 text-break">
                                                                @if($log->subject_type)
                                                                    {{ $log->subject_label }}
                                                                    <span class="text-muted">({{ $log->subject_type }})</span>
                                                                @else
                                                                    —
                                                                @endif
                                                            </dd>
                                                        </dl>
                                                    </div>

                                                    <div class="col-12 col-lg-6">
                                                        <div class="fw-bold text-muted text-uppercase mb-2" style="font-size:.7rem">Détail de l'action</div>
                                                        @if(count($log->detail_items))
                                                            <dl class="row mb-0 journal-dl">
                                                                @foreach($log->detail_items as $label => $value)
                                                                    <dt class="col-4">{{ $label }}</dt>
                                                                    <dd class="col-8">
                                                                        @if(is_array($value))
                                                                            @foreach($value as $item)
                                                                                <span class="badge bg-secondary-subtle text-dark border me-1 mb-1">{{ $item }}</span>
                                                                            @endforeach
                                                                        @else
                                                                            <span class="text-break">{{ $value }}</span>
                                                                        @endif
                                                                    </dd>
                                                                @endforeach
                                                            </dl>
                                                        @else
                                                            <span class="text-muted">Aucun détail supplémentaire enregistré pour cette action.</span>
                                                        @endif

                                                        @if($log->properties_json)
                                                            <details class="mt-2">
                                                                <summary class="text-muted" style="cursor:pointer">Données brutes</summary>
                                                                <pre class="journal-raw mb-0">{{ $log->properties_json }}</pre>
                                                            </details>
                                                        @endif

                                                        @if($log->user_agent)
                                                            <details class="mt-1">
                                                                <summary class="text-muted" style="cursor:pointer">User-Agent complet</summary>
                                                                <div class="text-break text-muted mt-1">{{ $log->user_agent }}</div>
                                                            </details>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
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

    /* La colonne « Détails » peut être longue : on la borne et on tronque. */
    .journal-table .journal-details {
        max-width: 320px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    /* Chevron du bouton d'expansion : pointe vers le bas quand la ligne est ouverte. */
    .journal-toggle i { transition: transform .15s ease-in-out; }
    .journal-toggle:not(.collapsed) i { transform: rotate(90deg); }

    .journal-dl dt { font-weight: 600; color: #6c757d; }
    .journal-dl dd { margin-bottom: .25rem; }

    .journal-raw {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: .25rem;
        padding: .5rem;
        font-size: .72rem;
        max-height: 240px;
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>
@endsection
