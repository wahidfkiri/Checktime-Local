<?php

namespace Vendor\BackupData\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Vendor\BackupData\Models\DataBackup;
use Vendor\BackupData\Services\DatabaseBackupService;

class BackupDataController extends Controller
{
    /**
     * Page principale : historique des exports.
     */
    public function index()
    {
        $backups = DataBackup::orderByDesc('created_at')->paginate(15);

        return view('backup_data::index', compact('backups'));
    }

    /**
     * Générer une nouvelle sauvegarde de la base (SQL + CSV dans un .zip).
     */
    public function export(Request $request, DatabaseBackupService $service)
    {
        try {
            @set_time_limit(0);

            $meta = $service->create();

            DataBackup::create(array_merge($meta, [
                'status'          => 'completed',
                'created_by'      => auth()->id(),
                'created_by_name' => auth()->user()->name ?? null,
            ]));

            return redirect()
                ->route('backup-data.index')
                ->with('success', 'Sauvegarde générée avec succès : ' . $meta['filename']
                    . ' (' . $meta['tables_count'] . ' tables, ' . $meta['rows_count'] . ' lignes).');

        } catch (\Throwable $e) {
            Log::error('Erreur backup base de données : ' . $e->getMessage());

            DataBackup::create([
                'filename'        => 'backup_' . now()->format('Y-m-d_H-i-s') . '.zip',
                'path'            => '',
                'size_bytes'      => 0,
                'tables_count'    => 0,
                'rows_count'      => 0,
                'status'          => 'failed',
                'error'           => $e->getMessage(),
                'created_by'      => auth()->id(),
                'created_by_name' => auth()->user()->name ?? null,
            ]);

            return redirect()
                ->route('backup-data.index')
                ->with('error', 'Erreur lors de la génération de la sauvegarde : ' . $e->getMessage());
        }
    }

    /**
     * Télécharger une archive existante.
     */
    public function download(DataBackup $backup)
    {
        if ($backup->status !== 'completed' || !$backup->path || !Storage::disk('local')->exists($backup->path)) {
            return redirect()
                ->route('backup-data.index')
                ->with('error', 'Fichier introuvable (il a peut-être été supprimé du serveur).');
        }

        return Storage::disk('local')->download($backup->path, $backup->filename);
    }

    /**
     * Supprimer une archive et son enregistrement d'historique.
     */
    public function destroy(DataBackup $backup)
    {
        try {
            if ($backup->path && Storage::disk('local')->exists($backup->path)) {
                Storage::disk('local')->delete($backup->path);
            }
            $backup->delete();

            return redirect()
                ->route('backup-data.index')
                ->with('success', 'Sauvegarde supprimée.');

        } catch (\Throwable $e) {
            Log::error('Erreur suppression backup : ' . $e->getMessage());

            return redirect()
                ->route('backup-data.index')
                ->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
}
