<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ficha del Empleado</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            color: #1a202c;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            background-color: #f3f4f6;
            padding: 5px 10px;
            margin-bottom: 10px;
            border-left: 4px solid #3b82f6;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #666;
            width: 30%;
        }
        .value {
            width: 70%;
        }
        .no-info {
            font-style: italic;
            color: #888;
            padding: 5px 10px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        table.data-table th {
            background-color: #f9fafb;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        @php
            $settingService = app(\App\Services\SettingService::class);
            $logoLight = $settingService->get('logo_light');
            
            $logoPath = null;
            if ($logoLight) {
                // If stored in public disk, get the full path
                $logoPath = public_path('storage/' . $logoLight);
            }
            
            // Fallback to default asset if not configured or file missing
            if (!$logoPath || !file_exists($logoPath)) {
                $logoPath = public_path('asset/images/logo-light.png');
                if (!file_exists($logoPath)) {
                    $logoPath = base_path('public/asset/images/logo-light.png');
                }
            }
            
            $logoData = '';
            if ($logoPath && file_exists($logoPath)) {
                try {
                    $logoData = base64_encode(file_get_contents($logoPath));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('PDF Logo Error: ' . $e->getMessage());
                }
            }
        @endphp

        @if($logoData)
            <img src="data:image/png;base64,{{ $logoData }}" class="logo" alt="Logo">
        @else
            <div style="display:none; color: red;">Logo not found at: {{ $logoPath }}</div>
            <h2>{{ config('app.name') }}</h2>
        @endif
        <h1 class="title">{{ __('nominas.pdf.title') }}</h1>
    </div>

    <!-- Detalles de Usuario -->
    <div class="section">
        <div class="section-title">{{ __('nominas.section.user_details') }}</div>
        <table class="info-grid">
            <tr>
                <td class="label">{{ __('nominas.field.name') }}:</td>
                <td class="value">{{ $record->user->name ?? __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.email') }}:</td>
                <td class="value">{{ $record->user->email ?? __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.roles') }}:</td>
                <td class="value">
                    @if($record->user && $record->user->roles->count() > 0)
                        {{ $record->user->roles->pluck('name')->join(', ') }}
                    @else
                        {{ __('nominas.pdf.no_info') }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Perfil de Empleado -->
    <div class="section">
        <div class="section-title">{{ __('nominas.section.employee_profile') }}</div>
        <table class="info-grid">
            <tr>
                <td class="label">{{ __('nominas.field.rut') }}:</td>
                <td class="value">{{ $record->rut ?? __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.birth_date') }}:</td>
                <td class="value">{{ $record->birth_date ? $record->birth_date->format('d-m-Y') : __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.age') }}:</td>
                <td class="value">{{ $record->birth_date ? $record->birth_date->age . ' ' . __('nominas.pdf.years_old') : __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.gender') }}:</td>
                <td class="value">{{ $record->gender ? __('nominas.options.gender.' . $record->gender) : __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.address') }}:</td>
                <td class="value">{{ $record->address ?? __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.phone') }}:</td>
                <td class="value">{{ $record->phone ?? __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.health_insurance') }}:</td>
                <td class="value">{{ $record->health_insurance ? __('nominas.options.health_insurance.' . $record->health_insurance) : __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.disability') }}:</td>
                <td class="value">{{ $record->disability ? __('nominas.pdf.yes') : __('nominas.pdf.no') }}</td>
            </tr>
        </table>
    </div>

    <!-- Contacto de Emergencia -->
    <div class="section">
        <div class="section-title">{{ __('nominas.pdf.emergency_contact') }}</div>
        @if($record->emergency_contact_name || $record->emergency_phone)
            <table class="info-grid">
                <tr>
                    <td class="label">{{ __('nominas.field.name') }}:</td>
                    <td class="value">{{ $record->emergency_contact_name ?? __('nominas.pdf.no_info') }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('nominas.field.phone') }}:</td>
                    <td class="value">{{ $record->emergency_phone ?? __('nominas.pdf.no_info') }}</td>
                </tr>
            </table>
        @else
            <div class="no-info">{{ __('nominas.pdf.no_info') }}</div>
        @endif
    </div>

    <!-- Información Laboral -->
    <div class="section">
        <div class="section-title">{{ __('nominas.pdf.labor_info') }}</div>
        <table class="info-grid">
            <tr>
                <td class="label">{{ __('nominas.field.position') }}:</td>
                <td class="value">{{ $record->cargo->name ?? __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.contract_type') }}:</td>
                <td class="value">{{ $record->contractType->name ?? __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.field.contract_date') }}:</td>
                <td class="value">{{ $record->contract_date ? $record->contract_date->format('d-m-Y') : __('nominas.pdf.no_info') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.pdf.contract_end_date') }}:</td>
                <td class="value">{{ $record->contract_end_date ? $record->contract_end_date->format('d-m-Y') : __('nominas.pdf.indefinite') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('nominas.pdf.pending_vacation_days') }}:</td>
                <td class="value">{{ $vacationBalance ?? '0' }} {{ __('nominas.pdf.business_days') }}</td>
            </tr>
        </table>
    </div>

    <!-- Datos Bancarios -->
    <div class="section">
        <div class="section-title">{{ __('nominas.section.bank_details') }}</div>
        @if($record->bank_name || $record->account_number)
            <table class="info-grid">
                <tr>
                    <td class="label">{{ __('nominas.pdf.bank') }}:</td>
                    <td class="value">{{ $record->bank_name ?? __('nominas.pdf.no_info') }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('nominas.field.account_type') }}:</td>
                    <td class="value">
                        {{ $record->account_type ? __('nominas.options.account_type.' . $record->account_type) : __('nominas.pdf.no_info') }}
                    </td>
                </tr>
                <tr>
                    <td class="label">{{ __('nominas.field.account_number') }}:</td>
                    <td class="value">{{ $record->account_number ?? __('nominas.pdf.no_info') }}</td>
                </tr>
            </table>
        @else
            <div class="no-info">{{ __('nominas.pdf.no_info') }}</div>
        @endif
    </div>

    <!-- Cargas Familiares (Hijos) -->
    <div class="section">
        <div class="section-title">{{ __('nominas.pdf.family_burdens') }}</div>
        @if(!empty($record->children) && is_array($record->children) && count($record->children) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('nominas.field.name') }}</th>
                        <th>{{ __('nominas.field.rut') }}</th>
                        <th>{{ __('nominas.field.birth_date') }}</th>
                        <th>{{ __('nominas.field.age') }}</th>
                        <th>{{ __('nominas.pdf.is_dependent') }}</th>
                        <th>{{ __('nominas.field.disability') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->children as $child)
                        <tr>
                            <td>{{ $child['name'] ?? '-' }}</td>
                            <td>{{ $child['rut'] ?? '-' }}</td>
                            <td>{{ isset($child['birth_date']) ? \Carbon\Carbon::parse($child['birth_date'])->format('d-m-Y') : '-' }}</td>
                            <td>{{ isset($child['birth_date']) ? \Carbon\Carbon::parse($child['birth_date'])->age . ' ' . __('nominas.pdf.years_old') : '-' }}</td>
                            <td>{{ isset($child['is_dependent']) && $child['is_dependent'] ? __('nominas.pdf.yes') : __('nominas.pdf.no') }}</td>
                            <td>{{ isset($child['has_disability']) && $child['has_disability'] ? __('nominas.pdf.yes') : __('nominas.pdf.no') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-info">{{ __('nominas.pdf.no_info') }}</div>
        @endif
    </div>

    <!-- Capacitaciones -->
    <div class="section">
        <div class="section-title">Capacitaciones y Certificaciones</div>
        @if(!empty($record->trainings) && is_array($record->trainings) && count($record->trainings) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Institución</th>
                        <th>Fecha</th>
                        <th>Horas</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->trainings as $training)
                        <tr>
                            <td>{{ $training['name'] ?? '-' }}</td>
                            <td>{{ $training['institution'] ?? '-' }}</td>
                            <td>{{ isset($training['date']) ? \Carbon\Carbon::parse($training['date'])->format('d-m-Y') : '-' }}</td>
                            <td>{{ $training['hours'] ?? '-' }}</td>
                            <td>
                                @php
                                    $trainingStatuses = [
                                        'completed' => 'Completado',
                                        'in_progress' => 'En Curso',
                                        'planned' => 'Planificado',
                                    ];
                                @endphp
                                {{ $trainingStatuses[$training['status'] ?? ''] ?? $training['status'] ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-info">Sin información</div>
        @endif
    </div>

    <!-- Licencias Médicas -->
    <div class="section">
        <div class="section-title">Licencias Médicas</div>
        @if($record->medicalLicenses->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Días</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->medicalLicenses as $license)
                        <tr>
                            <td>
                                @php
                                    $absenceTypes = [
                                        'licencia_medica' => 'Licencia Médica',
                                        'maternidad' => 'Maternidad',
                                        'accidente' => 'Accidente',
                                    ];
                                @endphp
                                {{ $absenceTypes[$license->absence_type] ?? $license->absence_type }}
                            </td>
                            <td>{{ $license->start_date ? $license->start_date->format('d-m-Y') : '-' }}</td>
                            <td>{{ $license->end_date ? $license->end_date->format('d-m-Y') : '-' }}</td>
                            <td>{{ $license->duration_days }}</td>
                            <td>
                                @php
                                    $licenseStatuses = [
                                        'active' => 'Activa',
                                        'closed' => 'Cerrada',
                                        'rejected' => 'Rechazada',
                                    ];
                                @endphp
                                {{ $licenseStatuses[strtolower($license->status)] ?? ucfirst($license->status) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-info">Sin información</div>
        @endif
    </div>

    <!-- Solicitudes de Ausencia -->
    <div class="section">
        <div class="section-title">{{ __('nominas.section.absence_requests') }}</div>
        @if($record->absenceRequests->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('nominas.absence_requests.column_id') }}</th>
                        <th>{{ __('nominas.absence_requests.column_type') }}</th>
                        <th>{{ __('nominas.absence_requests.column_start_date') }}</th>
                        <th>{{ __('nominas.absence_requests.column_end_date') }}</th>
                        <th>{{ __('nominas.absence_requests.column_days') }}</th>
                        <th>{{ __('nominas.absence_requests.column_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->absenceRequests as $request)
                        <tr>
                            <td>{{ $request->id }}</td>
                            <td>{{ $request->type->name ?? '-' }}</td>
                            <td>{{ $request->start_date ? $request->start_date->format('d-m-Y') : '-' }}</td>
                            <td>{{ $request->end_date ? $request->end_date->format('d-m-Y') : '-' }}</td>
                            <td>{{ $request->days_requested }}</td>
                            <td>
                                @php
                                    $normalizedStatus = str_replace(' ', '_', strtolower($request->status));
                                @endphp
                                {{ __('nominas.absence_requests.status.' . $normalizedStatus) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-info">{{ __('nominas.pdf.no_info') }}</div>
        @endif
    </div>

    <!-- Evaluaciones -->
    <div class="section">
        <div class="section-title">{{ __('nominas.section.evaluations') }}</div>
        <div class="no-info">{{ __('nominas.pdf.no_info') }}</div>
    </div>

    <!-- Desempeño / Tareas -->
    <div class="section">
        <div class="section-title">{{ __('nominas.section.tasks') }}</div>
        @if(isset($tasks) && $tasks->count() > 0)
            <div style="margin-bottom: 10px;">
                <span class="label">{{ __('nominas.pdf.performance_average') }}:</span>
                <span style="font-size: 14px; font-weight: bold; color: #d97706; font-family: 'DejaVu Sans', sans-serif;">
                    {{ $averageRating }} / 5.0 
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= round($averageRating))
                            ★
                        @else
                            ☆
                        @endif
                    @endfor
                </span>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">{{ __('nominas.tasks.column_id') }}</th>
                        <th style="width: 35%;">{{ __('nominas.tasks.column_title') }}</th>
                        <th style="width: 25%;">{{ __('nominas.tasks.column_creator') }}</th>
                        <th style="width: 15%;">{{ __('nominas.tasks.column_due_date') }}</th>
                        <th style="width: 15%;">{{ __('nominas.tasks.column_rating') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                        <tr>
                            <td>{{ $task->id }}</td>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->creator ? $task->creator->name : '-' }}</td>
                            <td>{{ $task->due_date ? $task->due_date->format('d-m-Y') : '-' }}</td>
                            <td style="color: #d97706; font-family: 'DejaVu Sans', sans-serif;">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $task->rating)
                                        ★
                                    @else
                                        ☆
                                    @endif
                                @endfor
                                <span style="color: #666; font-size: 10px; font-family: sans-serif;">({{ $task->rating }})</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-info">{{ __('nominas.pdf.no_rated_tasks') }}</div>
        @endif
    </div>

</body>
</html>