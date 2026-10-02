@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Contenu des emails</h3>
                        <p class="text-subtitle text-muted">Personnaliser le texte et le visuel des emails de rapports envoyés automatiquement ou manuellement</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Contenu des emails</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Le texte (salutation, introduction, signature, pied de page…) est librement modifiable.
                            Les blocs de données (statistiques, observations, période…) restent verrouillés :
                            ils sont toujours régénérés avec les vraies données à chaque envoi.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Email</th>
                                        <th style="width: 220px;">État</th>
                                        <th class="text-center" style="width: 140px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                        <tr>
                                            <td>{{ $template['label'] }}</td>
                                            <td>
                                                @if($template['customized'])
                                                    <span class="badge bg-success">Personnalisé</span>
                                                    @if($template['updated_at'])
                                                        <span class="text-muted small">le {{ \Carbon\Carbon::parse($template['updated_at'])->format('d/m/Y H:i') }}</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">Modèle par défaut</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('settings.email-templates.edit', $template['command']) }}"
                                                   target="_blank" rel="noopener" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-palette me-1"></i> Modifier
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
