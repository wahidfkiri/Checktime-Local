{{--
    Cartouche de signatures affiché en fin de rapport (aligné à droite).
    Colonnes = postes (Rédacteur, Vérificateur, Approbateur...),
    chaque poste pouvant contenir plusieurs responsables.
    Lignes : Nom & Prénom, Fonction, Date (vide), Visa (vide).
    Variable attendue : $signatairePostes (collection de SignatairePoste avec ->signataires)
--}}
@php
    $postes = $signatairePostes ?? collect();
    // Largeur des colonnes de postes répartie sur ~82% (le reste pour la colonne des libellés).
    $posteColWidth = $postes->count() > 0 ? round(82 / $postes->count(), 2) : 82;
@endphp

@if($postes->count())
<div style="margin-top: 22px; margin-bottom: 8px; width: 100%; page-break-inside: avoid;">
    <table align="right" style="width: 62%; border-collapse: collapse; font-size: 9px; color: #000;">
        <thead>
            <tr>
                <th style="border: none; background-color: transparent; width: 18%;">&nbsp;</th>
                @foreach($postes as $poste)
                    <th style="border: 1px solid #34495e; background-color: #ecf0f1; text-align: center;
                               padding: 5px 4px; width: {{ $posteColWidth }}%;
                               font-weight: bold; text-transform: uppercase;">
                        {{ $poste->name }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 1px solid #34495e; background-color: #f6f7f9; font-weight: bold;
                           text-align: left; padding: 5px 6px;">Nom &amp; Prénom</td>
                @foreach($postes as $poste)
                    <td style="border: 1px solid #34495e; text-align: center; padding: 5px 4px; vertical-align: middle;">
                        @forelse($poste->signataires as $s)
                            {{ $s->full_name }}@if(!$loop->last)<br>@endif
                        @empty
                            &nbsp;
                        @endforelse
                    </td>
                @endforeach
            </tr>
            <tr>
                <td style="border: 1px solid #34495e; background-color: #f6f7f9; font-weight: bold;
                           text-align: left; padding: 5px 6px;">Fonction</td>
                @foreach($postes as $poste)
                    <td style="border: 1px solid #34495e; text-align: center; padding: 5px 4px;
                               vertical-align: middle; font-style: italic; color: #555;">
                        @forelse($poste->signataires as $s)
                            {{ $s->fonction ?: '—' }}@if(!$loop->last)<br>@endif
                        @empty
                            &nbsp;
                        @endforelse
                    </td>
                @endforeach
            </tr>
            <tr>
                <td style="border: 1px solid #34495e; background-color: #f6f7f9; font-weight: bold;
                           text-align: left; padding: 5px 6px;">Date</td>
                @foreach($postes as $poste)
                    <td style="border: 1px solid #34495e; height: 26px;">&nbsp;</td>
                @endforeach
            </tr>
            <tr>
                <td style="border: 1px solid #34495e; background-color: #f6f7f9; font-weight: bold;
                           text-align: left; padding: 5px 6px;">Visa &amp; Cachet</td>
                @foreach($postes as $poste)
                    <td style="border: 1px solid #34495e; height: 55px;">&nbsp;</td>
                @endforeach
            </tr>
        </tbody>
    </table>
    <div style="clear: both;">&nbsp;</div>
</div>
@endif
