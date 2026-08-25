<?php

namespace App\Http\Controllers;

use App\Models\ReportTemplate;
use App\Reports\PresencePonctualiteColumns;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gestion des modèles d'édition PDF (colonnes à cocher) du rapport
 * Présence & Ponctualité.
 */
class ReportTemplateController extends Controller
{
    const REPORT_KEY = PresencePonctualiteColumns::REPORT_KEY;

    /**
     * Page de gestion des modèles.
     */
    public function index()
    {
        $templates = ReportTemplate::forReport(self::REPORT_KEY)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $groups = PresencePonctualiteColumns::groups();

        return view('settings.report-templates.index', compact('templates', 'groups'));
    }

    /**
     * Liste des modèles (AJAX, pour rafraîchir sans recharger la page).
     */
    public function list()
    {
        $templates = ReportTemplate::forReport(self::REPORT_KEY)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'templates' => $templates->map(fn ($t) => $this->present($t)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['report_key'] = self::REPORT_KEY;
        $data['created_by'] = auth()->id();

        $isDefault = (bool) $request->boolean('is_default');
        $template = ReportTemplate::create($data);

        if ($isDefault || ReportTemplate::forReport(self::REPORT_KEY)->count() === 1) {
            $template->markAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Modèle créé avec succès',
            'template' => $this->present($template->fresh()),
        ]);
    }

    public function update(Request $request, $id)
    {
        $template = ReportTemplate::forReport(self::REPORT_KEY)->findOrFail($id);

        $data = $this->validated($request, $template->id);
        $template->update($data);

        if ($request->boolean('is_default')) {
            $template->markAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Modèle mis à jour avec succès',
            'template' => $this->present($template->fresh()),
        ]);
    }

    public function destroy($id)
    {
        $template = ReportTemplate::forReport(self::REPORT_KEY)->findOrFail($id);

        if (ReportTemplate::forReport(self::REPORT_KEY)->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer le dernier modèle d'édition.",
            ], 422);
        }

        $wasDefault = $template->is_default;
        $template->delete();

        if ($wasDefault) {
            $next = ReportTemplate::forReport(self::REPORT_KEY)->orderBy('id')->first();
            if ($next) {
                $next->markAsDefault();
            }
        }

        return response()->json(['success' => true, 'message' => 'Modèle supprimé avec succès']);
    }

    public function setDefault($id)
    {
        $template = ReportTemplate::forReport(self::REPORT_KEY)->findOrFail($id);
        $template->markAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Modèle défini par défaut',
            'template' => $this->present($template->fresh()),
        ]);
    }

    public function duplicate($id)
    {
        $template = ReportTemplate::forReport(self::REPORT_KEY)->findOrFail($id);

        $baseName = $template->name . ' (copie)';
        $name = $baseName;
        $suffix = 1;
        while (ReportTemplate::forReport(self::REPORT_KEY)->where('name', $name)->exists()) {
            $suffix++;
            $name = $template->name . ' (copie ' . $suffix . ')';
        }

        $copy = ReportTemplate::create([
            'report_key' => self::REPORT_KEY,
            'name' => $name,
            'description' => $template->description,
            'columns' => $template->columns,
            'options' => $template->options,
            'is_default' => false,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Modèle dupliqué avec succès',
            'template' => $this->present($copy),
        ]);
    }

    private function validated(Request $request, $ignoreId = null): array
    {
        $known = array_keys(PresencePonctualiteColumns::all());

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('report_templates', 'name')
                    ->where(fn ($q) => $q->where('report_key', self::REPORT_KEY))
                    ->ignore($ignoreId),
            ],
            'description' => 'nullable|string|max:255',
            'columns' => 'required|array|min:1',
            'columns.*' => ['string', Rule::in($known)],
            'options.orientation' => 'nullable|in:portrait,landscape',
            'options.layout' => 'nullable|in:single,per_section',
            'options.edition' => 'nullable|in:standard,department',
            'options.show_totals' => 'nullable|boolean',
            'options.show_signatures' => 'nullable|boolean',
        ], [
            'name.required' => 'Le nom du modèle est obligatoire.',
            'name.unique' => 'Un modèle porte déjà ce nom.',
            'columns.required' => 'Cochez au moins une colonne.',
            'columns.min' => 'Cochez au moins une colonne.',
        ]);

        $data['columns'] = PresencePonctualiteColumns::sanitize($data['columns']);
        $data['options'] = PresencePonctualiteColumns::normalizeOptions($request->input('options'));

        return $data;
    }

    private function present(ReportTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'columns' => $template->resolvedColumns(),
            'options' => $template->resolvedOptions(),
            'is_default' => (bool) $template->is_default,
            'column_count' => count($template->resolvedColumns()),
            'updated_at' => optional($template->updated_at)->format('d/m/Y H:i'),
        ];
    }
}
