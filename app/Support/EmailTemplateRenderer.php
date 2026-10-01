<?php

namespace App\Support;

use App\Models\EmailTemplate;

/**
 * Permet à chaque rapport envoyé par email d'avoir un template personnalisé
 * (édité via l'éditeur Vvveb, bouton "Template" de /settings) tout en gardant
 * les données réelles (stats, tableaux) toujours à jour au moment de l'envoi.
 *
 * Le template personnalisé est un document HTML complet dans lequel
 * l'administrateur place un bloc <div id="vvveb-report-content">…</div> :
 * son contenu est remplacé, à l'envoi, par le rendu live du rapport. S'il
 * n'y a pas de template personnalisé (ou si ce bloc a été retiré), le
 * comportement par défaut (ou l'absence de données) s'applique — c'est le
 * risque accepté du mode "HTML libre".
 */
class EmailTemplateRenderer
{
    public const PLACEHOLDER_ID = 'vvveb-report-content';

    /**
     * Rend l'email pour $command : le template personnalisé s'il existe,
     * sinon la vue Blade par défaut.
     */
    public static function render(string $command, string $defaultView, array $viewData): string
    {
        $template = EmailTemplate::where('command', $command)->first();

        if (!$template || trim((string) $template->html) === '') {
            return view($defaultView, $viewData)->render();
        }

        $liveContent = self::extractContentBlock(view($defaultView, $viewData)->render());

        return self::injectContent($template->html, $liveContent);
    }

    /**
     * HTML de départ proposé dans l'éditeur : la vue par défaut telle
     * qu'elle est réellement envoyée aujourd'hui (avec le bloc de contenu
     * déjà repéré par son id), pour que personnaliser un template parte
     * toujours d'un rendu fidèle à l'email actuel.
     */
    public static function seed(string $defaultView, array $viewData): string
    {
        return view($defaultView, $viewData)->render();
    }

    /**
     * Extrait le HTML interne du bloc #vvveb-report-content d'un document
     * rendu, pour l'injecter tel quel dans un template personnalisé.
     */
    private static function extractContentBlock(string $html): string
    {
        $doc = self::parseHtml($html);
        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query('//*[@id="' . self::PLACEHOLDER_ID . '"]');

        if ($nodes->length === 0) {
            return '';
        }

        $inner = '';
        foreach ($nodes->item(0)->childNodes as $child) {
            $inner .= $doc->saveHTML($child);
        }

        return $inner;
    }

    /**
     * Remplace le contenu du bloc #vvveb-report-content du template
     * personnalisé par le HTML live donné. Si le bloc a été retiré par
     * l'administrateur, le template est renvoyé tel quel (sans données).
     */
    private static function injectContent(string $customHtml, string $liveContentHtml): string
    {
        $doc = self::parseHtml($customHtml);
        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query('//*[@id="' . self::PLACEHOLDER_ID . '"]');

        if ($nodes->length === 0) {
            return $customHtml;
        }

        $target = $nodes->item(0);
        while ($target->firstChild) {
            $target->removeChild($target->firstChild);
        }

        $fragmentDoc = self::parseHtml('<div id="__wrap">' . $liveContentHtml . '</div>');
        $fragmentXpath = new \DOMXPath($fragmentDoc);
        $wrapperNodes = $fragmentXpath->query('//*[@id="__wrap"]');
        $wrapper = $wrapperNodes->length > 0 ? $wrapperNodes->item(0) : null;

        if ($wrapper) {
            foreach (iterator_to_array($wrapper->childNodes) as $child) {
                $target->appendChild($doc->importNode($child, true));
            }
        }

        return self::saveHtml($doc);
    }

    private static function parseHtml(string $html): \DOMDocument
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);
        // Préfixe utilisé uniquement pour forcer un décodage UTF-8 correct
        // (sans lui, les caractères accentués sont mal interprétés) ; il est
        // retiré de la sortie par saveHtml(), libxml le laissant parfois
        // tel quel dans le HTML généré.
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $doc;
    }

    private static function saveHtml(\DOMDocument $doc): string
    {
        // libxml conserve parfois tel quel le préfixe <?xml encoding="UTF-8">
        // utilisé par parseHtml() au lieu de le consommer ; on le retire ici
        // (première occurrence seulement, où qu'elle apparaisse).
        return preg_replace('/<\?xml[^>]*>/i', '', $doc->saveHTML(), 1);
    }
}
