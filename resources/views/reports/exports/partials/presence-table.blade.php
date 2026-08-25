{{--
    Rendu d'un tableau du rapport Présence & Ponctualité pour un sous-ensemble
    de colonnes ($tableColumns), dans l'ordre du catalogue.

    Variables attendues :
    - $tableColumns : clés de colonnes à afficher dans ce tableau
    - $catalogue    : catalogue complet (PresencePonctualiteColumns::all())
    - $sortedData   : lignes du rapport, déjà triées
    - $showTotals   : bool, afficher la ligne de totaux
--}}
@php
    $headerBlocks = [];
    foreach ($tableColumns as $key) {
        $col = $catalogue[$key] ?? null;
        if (!$col) continue;
        $last = count($headerBlocks) ? $headerBlocks[count($headerBlocks) - 1] : null;
        if ($col['grouped'] && $last && $last['grouped'] && $last['group'] === $col['group']) {
            $headerBlocks[count($headerBlocks) - 1]['keys'][] = $key;
        } else {
            $headerBlocks[] = [
                'group' => $col['group'],
                'group_label' => $col['group_label'],
                'grouped' => $col['grouped'],
                'keys' => [$key],
            ];
        }
    }

    $showEmployeeCodeSubtext = in_array('employee_name', $tableColumns) && !in_array('employee_code', $tableColumns);
    $totals = $showTotals ? \App\Reports\PresencePonctualiteColumns::totals($tableColumns, $sortedData) : [];

    $leadingLabelSpan = 0;
    foreach ($tableColumns as $key) {
        if (array_key_exists($key, $totals)) break;
        $leadingLabelSpan++;
    }
    $leadingLabelSpan = max(1, $leadingLabelSpan);

    $rateClass = function ($rate) {
        $rate = (float) $rate;
        return $rate >= 90 ? 'rate-high' : ($rate >= 80 ? 'rate-medium' : 'rate-low');
    };
@endphp
<table>
    <thead>
        <tr>
            @foreach($headerBlocks as $block)
                @if($block['grouped'] && count($block['keys']) > 1)
                    <th colspan="{{ count($block['keys']) }}" class="section-title">{{ $block['group_label'] }}</th>
                @else
                    <th rowspan="2" style="width: {{ $catalogue[$block['keys'][0]]['width'] }}; text-align: {{ $catalogue[$block['keys'][0]]['align'] }};">
                        {{ $catalogue[$block['keys'][0]]['label'] }}
                    </th>
                @endif
            @endforeach
        </tr>
        <tr class="sub-header">
            @foreach($headerBlocks as $block)
                @if($block['grouped'] && count($block['keys']) > 1)
                    @foreach($block['keys'] as $key)
                        <th style="width: {{ $catalogue[$key]['width'] }};">{{ $catalogue[$key]['label'] }}</th>
                    @endforeach
                @endif
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($sortedData as $index => $employee)
        <tr>
            @foreach($tableColumns as $key)
                @php $col = $catalogue[$key]; $value = \App\Reports\PresencePonctualiteColumns::value($key, $employee, $index); @endphp
                @if($key === 'employee_name')
                    <td class="employee-name">
                        {{ $value }}
                        @if($showEmployeeCodeSubtext)
                            <br><small style="color: #666;">({{ $employee['employee_code'] }})</small>
                        @endif
                    </td>
                @elseif(in_array($key, ['presence_rate', 'ponctualite_rate']))
                    <td style="text-align: {{ $col['align'] }};">
                        <span class="{{ $rateClass($value) }}">{{ $value }}%</span>
                    </td>
                @elseif($key === 'observation')
                    <td style="text-align: left; font-size: 8px;">
                        {{ $value }}
                        <br><span class="total-rate">Total taux: {{ number_format($employee['total_rate'], 1) }}%</span>
                    </td>
                @else
                    <td style="text-align: {{ $col['align'] }};">{{ $value }}</td>
                @endif
            @endforeach
        </tr>
        @endforeach

        @if(!empty($totals))
        <tr class="total-row">
            <td colspan="{{ $leadingLabelSpan }}" style="text-align: right;"><strong>TOTAUX / MOYENNES :</strong></td>
            @foreach(array_slice($tableColumns, $leadingLabelSpan) as $key)
                @if(array_key_exists($key, $totals))
                    <td>
                        @if(in_array($key, ['presence_rate', 'ponctualite_rate']))
                            <strong class="{{ $rateClass($totals[$key]) }}">{{ $totals[$key] }}%</strong>
                        @else
                            <strong>{{ $totals[$key] }}</strong>
                        @endif
                    </td>
                @else
                    <td>-</td>
                @endif
            @endforeach
        </tr>
        @endif
    </tbody>
</table>
