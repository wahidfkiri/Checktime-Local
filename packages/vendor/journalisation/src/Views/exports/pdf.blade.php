<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Journal des activités</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #222; margin: 0; padding: 12px; }
        .header { border-bottom: 2px solid #333; margin-bottom: 10px; padding-bottom: 6px; }
        .title { font-size: 16px; font-weight: bold; }
        .meta { font-size: 9px; color: #555; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 3px 4px; text-align: left; vertical-align: top; }
        th { background: #eee; font-size: 8.5px; text-transform: uppercase; }
        td { font-size: 8.5px; }
        .badge { padding: 1px 4px; border-radius: 3px; color: #fff; font-size: 8px; }
        .b-login { background: #28a745; } .b-logout { background: #6c757d; }
        .b-login_failed { background: #dc3545; } .b-create { background: #0d6efd; }
        .b-update { background: #ffc107; color: #000; } .b-delete { background: #dc3545; }
        .b-export { background: #0dcaf0; color: #000; } .b-default { background: #343a40; }
        .footer { margin-top: 8px; font-size: 8px; color: #777; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Journal des activités</div>
        <div class="meta">
            {{ $client->name ?? 'CheckTime' }} — Exporté le {{ $export_date->format('d/m/Y H:i') }} —
            {{ $logs->count() }} enregistrement(s)
            @if(!empty(array_filter($filters)))
                <br>Filtres :
                @if(!empty($filters['action'])) action = {{ $filters['action'] }} ; @endif
                @if(!empty($filters['date_from'])) du {{ $filters['date_from'] }} ; @endif
                @if(!empty($filters['date_to'])) au {{ $filters['date_to'] }} ; @endif
                @if(!empty($filters['search'])) recherche « {{ $filters['search'] }} » @endif
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:13%;">Date &amp; heure</th>
                <th style="width:15%;">Utilisateur</th>
                <th style="width:10%;">Action</th>
                <th style="width:32%;">Description</th>
                <th style="width:14%;">Objet</th>
                <th style="width:16%;">IP / Route</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user_name ?? '—' }}</td>
                    <td><span class="badge b-{{ $log->action }}">{{ $log->action_label }}</span></td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->subject_short ? $log->subject_short . ' #' . $log->subject_id : '—' }}</td>
                    <td>{{ $log->ip_address }}<br><span style="color:#888;">{{ $log->route }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:20px;">Aucune activité.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">CheckTime — Journal des activités</div>
</body>
</html>
