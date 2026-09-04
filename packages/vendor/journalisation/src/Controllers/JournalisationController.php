<?php

namespace Vendor\Journalisation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SimpleXlsxWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Vendor\Journalisation\Models\ActivityLog;

class JournalisationController extends Controller
{
    /**
     * Page principale : historique des activités (filtrable, paginé).
     */
    public function index(Request $request)
    {
        $logs = $this->filteredQuery($request)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('journalisation::index', [
            'logs'    => $logs,
            'users'   => $this->usersForFilter(),
            'actions' => ActivityLog::ACTION_LABELS,
            'filters' => $this->currentFilters($request),
        ]);
    }

    /**
     * Export Excel (.xlsx) des activités filtrées.
     */
    public function exportExcel(Request $request)
    {
        $logs = $this->filteredQuery($request)->orderByDesc('created_at')->get();

        $xlsx = new SimpleXlsxWriter('Journal des activités');
        $xlsx->setColumnWidths([20, 26, 18, 60, 16, 30]);

        $xlsx->addRow(['Journal des activités — exporté le ' . now()->format('d/m/Y H:i')], true);
        $xlsx->addRow([]);
        $xlsx->addRow([
            'Date & heure', 'Utilisateur', 'Action', 'Description',
            'Adresse IP', 'Route',
        ], true);

        foreach ($logs as $log) {
            $xlsx->addRow([
                optional($log->created_at)->format('d/m/Y H:i:s'),
                (string) ($log->user_name ?? '—'),
                (string) $log->action_label,
                (string) ($log->description ?? ''),
                (string) ($log->ip_address ?? ''),
                (string) ($log->route ?? ''),
            ]);
        }

        $filename = 'journal_activites_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        return $xlsx->download($filename);
    }

    /**
     * Export PDF des activités filtrées.
     */
    public function exportPdf(Request $request)
    {
        $logs = $this->filteredQuery($request)->orderByDesc('created_at')->limit(5000)->get();

        $data = [
            'logs'        => $logs,
            'export_date' => now(),
            'filters'     => $this->currentFilters($request),
            'client'      => \App\Models\Setting::company(),
        ];

        $pdf = Pdf::loadView('journalisation::exports.pdf', $data)->setPaper('A4', 'landscape');

        $filename = 'journal_activites_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $pdf->download($filename);
    }

    // ────────────────────────────────────────────────────────────────

    /**
     * Construit la requête filtrée commune (liste + exports).
     */
    private function filteredQuery(Request $request)
    {
        $query = ActivityLog::query();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', $term)
                  ->orWhere('route', 'like', $term)
                  ->orWhere('url', 'like', $term)
                  ->orWhere('user_name', 'like', $term)
                  ->orWhere('ip_address', 'like', $term);
            });
        }

        return $query;
    }

    private function currentFilters(Request $request): array
    {
        return [
            'user_id'   => $request->input('user_id'),
            'action'    => $request->input('action'),
            'date_from' => $request->input('date_from'),
            'date_to'   => $request->input('date_to'),
            'search'    => $request->input('search'),
        ];
    }

    /**
     * Liste (id => nom) pour le filtre utilisateur. Le nom est déchiffré par le modèle.
     */
    private function usersForFilter(): array
    {
        return User::orderBy('id')->get()->mapWithKeys(function ($user) {
            $name = null;
            try {
                $name = $user->name;
            } catch (\Throwable $e) {
                $name = $user->email;
            }
            return [$user->id => ($name ?: $user->email ?: ('Utilisateur #' . $user->id))];
        })->toArray();
    }
}
