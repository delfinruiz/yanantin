<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ __('surveys.report.title') }}</title>
    <style>
        @page { margin: 20mm 15mm; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 12px; }
        .header { text-align: center; margin-bottom: 12px; }
        .logo { height: 64px; }
        .title { font-size: 20px; font-weight: 700; margin-top: 6px; }
        .subtitle { font-size: 12px; color: #6b7280; }
        .section { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-top: 12px; page-break-inside: auto; }
        .section-list { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-top: 12px; page-break-inside: auto; }
        .dimension { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-top: 12px; page-break-inside: auto; }
        .dim-head { page-break-after: avoid; margin-bottom: 6px; }
        .grid { display: table; width: 100%; table-layout: fixed; }
        .grid .col { display: table-cell; padding: 8px; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-top: 6px; page-break-inside: auto; }
        .table th, .table td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .table tr, .table td, .table th { page-break-inside: avoid; }
        .table thead { display: table-header-group; }
        .table tfoot { display: table-footer-group; }
        .dim-title { page-break-after: avoid; }
        .bar-wrap { width: 100%; height: 10px; background: #e5e7eb; border-radius: 6px; overflow: hidden; }
        .bar { height: 10px; background: #1A2A4F; }
        .dim-title { font-weight: 600; margin-bottom: 6px; }
        .footer { margin-top: 16px; font-size: 10px; color: #6b7280; text-align: center; }
        .reliability-box { border-width: 1px; border-style: solid; }
        .reliability-box.reliability-high { border-color: #10b981; }
        .reliability-box.reliability-medium { border-color: #f59e0b; }
        .reliability-box.reliability-low { border-color: #ef4444; }
        .reliability-box.reliability-high .dim-title { color: #10b981; }
        .reliability-box.reliability-medium .dim-title { color: #f59e0b; }
        .reliability-box.reliability-low .dim-title { color: #ef4444; }
        .bar-high { background: #10b981; }
        .bar-medium { background: #f59e0b; }
        .bar-low { background: #ef4444; }
        .icon { display:inline-block; vertical-align:middle; margin-right:6px; }
        .icon svg { width:14px; height:14px; }
        .icon-high svg { fill:#10b981; }
        .icon-medium svg { fill:#f59e0b; }
        .icon-low svg { fill:#ef4444; }
    </style>
</head>
<body>
    <div class="header">
        @if($logo)
            <img src="{{ $logo }}" alt="Logo" class="logo">
        @endif
        <div class="title">{{ $survey->title }}</div>
        <div class="subtitle">{{ $company }}</div>
    </div>

    <div class="section">
        <div><strong>{{ __('surveys.report.description') }}</strong> {{ $survey->description }}</div>
        <div class="grid" style="margin-top:6px;">
            <div class="col">
                <div><strong>{{ __('surveys.report.responded') }}</strong> {{ $responded_count ?? 0 }}</div>
                @if(!is_null($participants))
                    <div><strong>{{ $participants_label ?? __('surveys.report.participants') }}:</strong> {{ $participants }}</div>
                @endif
            </div>
            <div class="col"><strong>{{ __('surveys.report.global_avg') }}</strong> {{ $globalAvg ?? __('surveys.report.na') }}</div>
            <div class="col"><strong>{{ __('surveys.report.deadline') }}</strong> {{ $survey->deadline ? \Illuminate\Support\Carbon::parse($survey->deadline)->format('d/m/Y H:i') : __('surveys.report.na') }}</div>
        </div>
        @if(!is_null($globalAvg))
            @php $globalPercent = min(100, max(0, $globalAvg)); @endphp
            <div style="margin-top:8px;">
                <div class="dim-title">{{ ($weighted ?? false) ? __('surveys.report.weighted_avg') : __('surveys.report.global_avg') }}</div>
                <div class="bar-wrap" style="height:10px;">
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="{{ $globalPercent }}%" style="background:#1A2A4F;height:10px;"></td>
                            <td width="{{ 100 - $globalPercent }}%" style="background:#e5e7eb;height:10px;"></td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @if(!is_null($participation_pct))
    <div class="section reliability-box {{ $reliability_class ? 'reliability-' . $reliability_class : '' }}" style="margin-top:10px;">
        <div class="dim-title">
            <span class="icon">
                @if(($reliability_class ?? 'low') === 'low')
                    <svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 1 L15 15 L1 15 Z" fill="{{ $reliability_color ?? '#ef4444' }}" />
                    </svg>
                @elseif(($reliability_class ?? 'low') === 'medium')
                    <svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="8" cy="8" r="7" fill="{{ $reliability_color ?? '#f59e0b' }}" />
                    </svg>
                @else
                    <svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="8" cy="8" r="7" fill="{{ $reliability_color ?? '#10b981' }}" />
                    </svg>
                @endif
            </span>
            Confiabilidad de resultados: {{ $reliability }}
        </div>
        <div style="font-size:12px; color:#374151;">
            Participación: {{ number_format($participation_pct, 2) }}%
        </div>
        <div style="margin-top:6px;">
            <div class="bar-wrap" style="height:10px;">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="{{ min(100, max(0, $participation_pct)) }}%" class="bar-{{ $reliability_class ?? 'low' }}" style="height:10px;"></td>
                        <td width="{{ 100 - min(100, max(0, $participation_pct)) }}%" style="background:#e5e7eb;height:10px;"></td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="margin-top:6px; font-size:11px; color:#6b7280;">
            Clasificación de confiabilidad:
            <span style="color:#10b981;">≥ 70% → Alta</span>,
            <span style="color:#f59e0b;">40–70% → Media</span>,
            <span style="color:#ef4444;">&lt; 40% → Baja</span>
        </div>
    </div>
    @endif

    @if(!empty($respondents))
    <div class="section">
        <div class="dim-title">Participantes que respondieron</div>
        <table class="table">
            <thead>
                <tr><th>Nombre</th></tr>
            </thead>
            <tbody>
                @foreach($respondents as $name)
                    <tr><td>{{ $name }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="section-list">
        <div class="dim-title">Resumen por dimensión</div>
        @foreach($dimensions as $dim => $info)
            <div class="dimension">
                <div class="dim-head" style="display:flex; justify-content:space-between;">
                    <span><strong>{{ $dim }}</strong></span>
                    <span>Promedio: {{ $info['avg'] ?? 'N/A' }} • Meta: {{ $info['kpi'] ?? 'N/A' }} • %Cumplimiento: {{ $info['compliance_pct'] ?? 'N/A' }}{{ isset($info['compliance_pct']) ? '%' : '' }} • {{ $info['rating'] ?? '' }} @if(isset($info['weight']) && $info['weight'] !== null) • Peso: {{ number_format((float)$info['weight'], 2) }} @endif</span>
                </div>
                @php
                    $avg = isset($info['avg']) ? (float) $info['avg'] : null;
                    $percent = $avg !== null ? min(100, max(0, $avg)) : 0;
                    $kpi = isset($info['kpi']) ? (float) $info['kpi'] : null;
                    $kpiPct = $kpi !== null ? min(100, max(0, $kpi)) : 0;
                @endphp
                <div class="bar-wrap" style="height:10px;">
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="{{ $percent }}%" style="background:#1A2A4F;height:10px;"></td>
                            <td width="{{ 100 - $percent }}%" style="background:#e5e7eb;height:10px;"></td>
                        </tr>
                    </table>
                </div>
                <div class="bar-wrap" style="height:10px; margin-top:4px;">
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="{{ $kpiPct }}%" style="background:#f59e0b;height:10px;"></td>
                            <td width="{{ 100 - $kpiPct }}%" style="background:#e5e7eb;height:10px;"></td>
                        </tr>
                    </table>
                </div>
                <table class="table" style="margin-top:6px;">
                    <tbody>
                        <tr><td>Preguntas</td><td>{{ $info['questions_count'] }}</td></tr>
                        <tr><td>Respuestas</td><td>{{ $info['responses_count'] }}</td></tr>
                        <tr><td>Meta (KPI)</td><td>{{ $info['kpi'] ?? 'N/A' }}</td></tr>
                        <tr><td>% Cumplimiento</td><td>{{ isset($info['compliance_pct']) ? number_format($info['compliance_pct'], 2) . '%' : 'N/A' }}</td></tr>
                        <tr><td>Calificación</td><td>{{ $info['rating'] ?? 'N/A' }}</td></tr>
                        @if(isset($info['weight']) && $info['weight'] !== null)
                            <tr><td>Peso</td><td>{{ number_format((float)$info['weight'], 2) }}</td></tr>
                        @endif
                        @if(isset($info['bool_yes_pct']) && $info['bool_yes_pct'] !== null)
                            <tr><td>% Sí (binario)</td><td>{{ number_format((float)$info['bool_yes_pct'], 2) }}%</td></tr>
                        @endif
                        @if(isset($info['multi_count']) && $info['multi_count'] > 0)
                            <tr><td>Respuestas selección múltiple</td><td>{{ $info['multi_count'] }}</td></tr>
                        @endif
                        @if(isset($info['text_count']) && $info['text_count'] > 0)
                            <tr><td>Comentarios (texto)</td><td>{{ $info['text_count'] }}</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>


    <div class="section-list" style="margin-top:12px;">
        <div class="dim-title">Resumen por tipo de pregunta</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Preguntas</th>
                    <th>Respuestas</th>
                    <th>Métrica</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Escala 0–5</td>
                    <td>{{ $type_summary['scale_5']['count'] ?? 0 }}</td>
                    <td>{{ $type_summary['scale_5']['responses'] ?? 0 }}</td>
                    <td>Promedio {{ isset($type_summary['scale_5']['avg']) ? number_format($type_summary['scale_5']['avg'],2).'%' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Escala 0–10</td>
                    <td>{{ $type_summary['scale_10']['count'] ?? 0 }}</td>
                    <td>{{ $type_summary['scale_10']['responses'] ?? 0 }}</td>
                    <td>Promedio {{ isset($type_summary['scale_10']['avg']) ? number_format($type_summary['scale_10']['avg'],2).'%' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Likert (1–5)</td>
                    <td>{{ $type_summary['likert']['count'] ?? 0 }}</td>
                    <td>{{ $type_summary['likert']['responses'] ?? 0 }}</td>
                    <td>Promedio {{ isset($type_summary['likert']['avg']) ? number_format($type_summary['likert']['avg'],2).'%' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Sí/No</td>
                    <td>{{ $type_summary['bool']['count'] ?? 0 }}</td>
                    <td>{{ $type_summary['bool']['responses'] ?? 0 }}</td>
                    <td>% Sí {{ isset($type_summary['bool']['yes_pct']) ? number_format($type_summary['bool']['yes_pct'],2).'%' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Selección múltiple</td>
                    <td>{{ $type_summary['multi']['count'] ?? 0 }}</td>
                    <td>{{ $type_summary['multi']['responses'] ?? 0 }}</td>
                    <td>Conteo</td>
                </tr>
                <tr>
                    <td>Texto</td>
                    <td>{{ $type_summary['text']['count'] ?? 0 }}</td>
                    <td>{{ $type_summary['text']['responses'] ?? 0 }}</td>
                    <td>Conteo</td>
                </tr>
            </tbody>
        </table>
    </div>

    @php
        $worst = collect($pie_slices ?? [])->filter(fn($d) => ($d['avg'] ?? 0) > 0)->sortBy('avg')->take(3)->values()->all();
        $hasPie = !empty($pie_png);
    @endphp
    @if($hasPie)
    <div class="section-list" style="margin-top:18px;">
        <div class="dim-title">Distribución promedio por dimensión</div>
        <div class="grid" style="margin-top:6px;">
            <div class="col" style="width:40%; text-align:center;">
                <img src="{{ $pie_png }}" alt="Gráfico de torta" style="width:160px;height:160px; display:inline-block;">
            </div>
            <div class="col" style="width:60%;">
                <table class="table">
                    <thead>
                        <tr><th>Dimensión</th><th>Participación</th><th>Promedio</th></tr>
                    </thead>
                    <tbody>
                        @foreach($pie_slices as $slice)
                            <tr>
                                <td>
                                    @if(!empty($slice['swatch']))
                                        <img src="{{ $slice['swatch'] }}" alt="" style="width:10px;height:10px;margin-right:6px;vertical-align:middle;">
                                    @endif
                                    {{ $slice['dim'] }}
                                </td>
                                <td>{{ number_format($slice['pct'], 2) }}%</td>
                                <td>{{ $slice['avg'] > 0 ? number_format($slice['avg'], 2) : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @if(!empty($worst))
    <div class="section-list" style="margin-top:12px;">
        <div class="dim-title">Top 3 dimensiones con peor promedio</div>
        <table class="table">
            <thead>
                <tr><th>Dimensión</th><th>Promedio</th></tr>
            </thead>
            <tbody>
                @foreach($worst as $row)
                    <tr>
                        <td style="color:#ef4444;font-weight:600;">{{ $row['dim'] }}</td>
                        <td style="color:#ef4444;font-weight:600;">{{ number_format($row['avg'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="section-list" style="margin-top:18px;">
        <div class="dim-title">Visualización general (barras por dimensión, 0–100)</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Dimensión</th>
                    <th style="width:70%;">Promedio (0–100)</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dimensions as $dim => $info)
                    @php
                        $avg = isset($info['avg']) ? (float) $info['avg'] : null;
                        $percent = $avg !== null ? min(100, max(0, $avg)) : 0;
                    @endphp
                    <tr>
                        <td style="page-break-inside: avoid;"><strong>{{ $dim }}</strong></td>
                        <td style="page-break-inside: avoid;">
                            <div class="bar-wrap" style="height:10px;">
                                <table width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="{{ $percent }}%" style="background:#1A2A4F;height:10px;"></td>
                                        <td width="{{ 100 - $percent }}%" style="background:#e5e7eb;height:10px;"></td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                        <td style="page-break-inside: avoid;">{{ $avg !== null ? number_format($avg, 2) : 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section" style="margin-top: 12px; page-break-inside: avoid;">
        <div class="dim-title" style="font-size: 14px; margin-bottom: 10px; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px;">
            Apreciación Inteligencia Artificial
        </div>
        @if($survey->aiAppreciation)
            <div style="white-space: pre-wrap; text-align: left; line-height: 1.6; color: #374151;">{{ $survey->aiAppreciation->content }}</div>
        @else
            <div style="font-style: italic; color: #6b7280; padding: 10px 0;">Sin apreciación</div>
        @endif
    </div>

    <div class="section-list" style="margin-top:12px;">
        <div class="dim-title">Metodología de cálculo</div>
        <ul style="margin:6px 0 0 16px;">
            <li>Normalización a 0–100:
                <ul style="margin-top:4px;">
                    <li>Escala 0–5 → (valor/5)×100</li>
                    <li>Escala 0–10 → (valor/10)×100</li>
                    <li>Likert (1–5) → ((valor−1)/4)×100</li>
                </ul>
            </li>
            <li>Cumplimiento vs KPI: (promedio_normalizado / KPI)×100 (KPI en 0–100)</li>
            <li>Ponderación: promedio ponderado si hay pesos, si no promedio simple</li>
            <li>Tipos no cuantitativos:
                <ul style="margin-top:4px;">
                    <li>Sí/No → se reporta % de “Sí”</li>
                    <li>Selección múltiple → conteo de respuestas</li>
                    <li>Texto → conteo de comentarios</li>
                </ul>
            </li>
        </ul>
    </div>
    <div class="footer">
        Generado automáticamente • {{ \Illuminate\Support\Carbon::now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
