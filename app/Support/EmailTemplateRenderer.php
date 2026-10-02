<?php

namespace App\Support;

use App\Models\EmailTemplate;

/**
 * Permet à chaque rapport envoyé par email d'avoir un template personnalisé
 * (édité via l'éditeur Vvveb, bouton "Template" de /settings) tout en gardant
 * les données réelles (stats, observations…) toujours à jour au moment de
 * l'envoi, sans perdre le texte libre que l'administrateur a réécrit autour.
 *
 * Principe : dans les vues par défaut, chaque élément dont le contenu vient de
 * la base porte un id unique et l'un de ces deux marqueurs :
 *  - `data-vvveb-disabled` : bloc (grille de stats, "Détails", observations…)
 *    affiché verrouillé dans l'éditeur (voir vvvebjs-editor-helpers.css) ;
 *  - `data-vvveb-dynamic` : valeur en ligne dans une phrase (nom de
 *    l'employé, dates de période, année…), surlignée dans l'éditeur mais
 *    laissée dans le fil du texte pour que la phrase reste éditable.
 * Tout le reste (salutation, intro, bouton, signature…) est du texte normal,
 * librement éditable et conservé tel quel.
 *
 * À l'envoi : on prend le template personnalisé de l'administrateur tel
 * quel, puis pour chaque bloc verrouillé qu'il contient, on régénère son
 * contenu à partir d'un rendu frais de la vue par défaut avec les données
 * réelles du destinataire — en le retrouvant par son id. Un bloc verrouillé
 * absent du rendu frais (ex. observations vides cette semaine, caché par un
 * @if) est retiré plutôt que de laisser un ancien contenu figé visible.
 */
class EmailTemplateRenderer
{
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

        $liveZones = self::extractDisabledZones(view($defaultView, $viewData)->render());

        return self::injectZones($template->html, $liveZones);
    }

    /**
     * HTML de départ proposé dans l'éditeur : la vue par défaut telle
     * qu'elle est réellement envoyée aujourd'hui (avec ses blocs dynamiques
     * déjà marqués verrouillés), pour que personnaliser un template parte
     * toujours d'un rendu fidèle à l'email actuel.
     */
    public static function seed(string $defaultView, array $viewData): string
    {
        return view($defaultView, $viewData)->render();
    }

    /**
     * Repère chaque bloc `[data-vvveb-disabled][id]` d'un document rendu et
     * renvoie son HTML interne, indexé par id.
     */
    private static function extractDisabledZones(string $html): array
    {
        $doc = self::parseHtml($html);
        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query('//*[@id][@data-vvveb-disabled or @data-vvveb-dynamic]');

        $zones = [];
        foreach ($nodes as $node) {
            $id = $node->getAttribute('id');
            if ($id === '') {
                continue;
            }

            $inner = '';
            foreach ($node->childNodes as $child) {
                $inner .= $doc->saveHTML($child);
            }
            $zones[$id] = $inner;
        }

        return $zones;
    }

    /**
     * Dans le template personnalisé, remplace le contenu de chaque bloc
     * verrouillé trouvé dans $zones (par id) ; retire les blocs verrouillés
     * dont l'id n'a pas de correspondance dans $zones (donnée absente de ce
     * rendu, ex. observations vides).
     */
    private static function injectZones(string $customHtml, array $zones): string
    {
        $doc = self::parseHtml($customHtml);
        $xpath = new \DOMXPath($doc);
        $lockedNodes = $xpath->query('//*[@id][@data-vvveb-disabled or @data-vvveb-dynamic]');

        foreach (iterator_to_array($lockedNodes) as $node) {
            $id = $node->getAttribute('id');

            if (!array_key_exists($id, $zones)) {
                $node->parentNode?->removeChild($node);
                continue;
            }

            while ($node->firstChild) {
                $node->removeChild($node->firstChild);
            }

            $fragmentDoc = self::parseHtml('<div id="__wrap">' . $zones[$id] . '</div>');
            $fragmentXpath = new \DOMXPath($fragmentDoc);
            $wrapperNodes = $fragmentXpath->query('//*[@id="__wrap"]');
            $wrapper = $wrapperNodes->length > 0 ? $wrapperNodes->item(0) : null;

            if ($wrapper) {
                foreach (iterator_to_array($wrapper->childNodes) as $child) {
                    $node->appendChild($doc->importNode($child, true));
                }
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
