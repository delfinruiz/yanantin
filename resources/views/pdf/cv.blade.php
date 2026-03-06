<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Curriculum Vitae - {{ $user->name }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #f3f4f6;
            padding: 30px;
            border-bottom: 1px solid #e5e7eb;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .avatar-cell {
            width: 120px;
            vertical-align: top;
        }
        .avatar-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            background-color: #f3f4f6;
        }
        .avatar-initials {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #4f46e5;
            color: #fff;
            text-align: center;
            line-height: 100px;
            font-size: 36px;
            font-weight: bold;
            border: 3px solid #fff;
        }
        .info-cell {
            vertical-align: top;
            padding-left: 20px;
        }
        .name {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 5px 0;
            color: #111827;
        }
        .contact-info {
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 10px;
        }
        .contact-item {
            margin-right: 15px;
            display: inline-block;
        }
        .links {
            margin-top: 10px;
        }
        .link-badge {
            background-color: #e0e7ff;
            color: #4338ca;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            text-decoration: none;
            margin-right: 5px;
        }
        .salary-box {
            background-color: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            width: 180px;
            float: right;
        }
        .salary-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #9ca3af;
            letter-spacing: 0.05em;
        }
        .salary-value {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            margin: 5px 0;
        }
        .availability-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .bg-green { background-color: #d1fae5; color: #065f46; }
        .bg-yellow { background-color: #fef3c7; color: #92400e; }

        .content {
            padding: 30px;
        }
        .layout-table {
            width: 100%;
            border-collapse: collapse;
        }
        .main-col {
            width: 65%;
            vertical-align: top;
            padding-right: 30px;
        }
        .sidebar-col {
            width: 35%;
            vertical-align: top;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        .item {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .item-title {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }
        .item-subtitle {
            font-size: 12px;
            color: #4f46e5;
            font-weight: bold;
        }
        .item-date {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }
        .item-description {
            font-size: 12px;
            color: #374151;
            margin-top: 5px;
            white-space: pre-line;
        }
        .skill-badge {
            display: inline-block;
            background-color: #f3f4f6;
            color: #1f2937;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-right: 4px;
            margin-bottom: 4px;
        }
        .language-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .soft-skill-item {
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 5px;
        }
        .status-badge {
            background-color: #e5e7eb;
            color: #374151;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            float: right;
        }
    </style>
</head>
<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="avatar-cell">
                    @if($user->avatar_url && file_exists(storage_path('app/public/' . $user->avatar_url)))
                        <img src="{{ storage_path('app/public/' . $user->avatar_url) }}" class="avatar-img" alt="Avatar">
                    @elseif($user->avatar_url)
                        <img src="{{ Filament\Facades\Filament::getUserAvatarUrl($user) }}" class="avatar-img" alt="Avatar">
                    @else
                        <div class="avatar-initials">
                            {{ strtoupper(substr($user->name, 0, 1) . (str_contains($user->name, ' ') ? substr(explode(' ', $user->name)[1], 0, 1) : '')) }}
                        </div>
                    @endif
                </td>
                <td class="info-cell">
                    <h1 class="name" style="margin-top: 10px;">{{ $user->name }}</h1>
                    
                    <div class="contact-info" style="line-height: 1.6; width: 100%;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <!-- Columna Izquierda: Contacto y Ubicación -->
                                <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                                    <div style="margin-bottom: 5px;">
                                        <span class="contact-item"><strong>Email:</strong><br>{{ $user->email }}</span>
                                    </div>
                                    @if($profile->phone)
                                        <div style="margin-bottom: 5px;">
                                            <span class="contact-item"><strong>Teléfono:</strong><br>{{ $profile->phone }}</span>
                                        </div>
                                    @endif
                                    @if($profile->city || $profile->country)
                                        <div style="margin-bottom: 5px;">
                                            <span class="contact-item"><strong>Ubicación:</strong><br>{{ $profile->city }}, {{ $profile->country }}</span>
                                        </div>
                                    @endif
                                </td>
                                
                                <!-- Columna Derecha: Datos Personales y Disponibilidad -->
                                <td style="width: 50%; vertical-align: top;">
                                    <div style="margin-bottom: 5px;">
                                        <span class="contact-item"><strong>RUT/DNI:</strong> {{ $profile->rut }}</span>
                                    </div>
                                    @if($profile->birth_date)
                                        <div style="margin-bottom: 5px;">
                                            <span class="contact-item"><strong>Edad:</strong> {{ \Carbon\Carbon::parse($profile->birth_date)->age }} años</span>
                                        </div>
                                    @endif
                                    <div style="margin-bottom: 5px;">
                                        <span class="contact-item"><strong>Modalidad:</strong> {{ $profile->modality_availability ?? 'No especificada' }}</span>
                                    </div>
                                    <div style="margin-bottom: 5px;">
                                        <span class="contact-item"><strong>Traslado:</strong> {{ $profile->relocation_availability ? 'Disponible' : 'No disponible' }}</span>
                                    </div>
                                    <div style="margin-top: 5px;">
                                        <span class="availability-badge {{ $profile->immediate_availability ? 'bg-green' : 'bg-yellow' }}" style="margin-left: 0;">
                                            Disponibilidad: {{ $profile->immediate_availability ? 'Inmediata' : 'Por acordar' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="links" style="margin-top: 15px;">
                        @if($profile->linkedin_url)
                            <a href="{{ $profile->linkedin_url }}" class="link-badge">LinkedIn</a>
                        @endif
                        @if($profile->portfolio_url)
                            <a href="{{ $profile->portfolio_url }}" class="link-badge">Portafolio</a>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <table class="layout-table">
            <tr>
                <!-- Main Column -->
                <td class="main-col">
                    
                    @if($profile->education)
                    <div class="section">
                        <div class="section-title">Formación Académica</div>
                        @foreach($profile->education as $edu)
                            <div class="item">
                                <span class="status-badge">{{ $edu['status'] ?? '' }}</span>
                                <div class="item-title">{{ $edu['title'] ?? '' }}</div>
                                <div class="item-subtitle">{{ $edu['institution'] ?? '' }}</div>
                                <div class="item-date">
                                    {{ isset($edu['start_date']) ? \Carbon\Carbon::parse($edu['start_date'])->format('Y') : '' }} 
                                    - 
                                    {{ isset($edu['end_date']) ? \Carbon\Carbon::parse($edu['end_date'])->format('Y') : 'Actualidad' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif

                    @if($profile->work_experience)
                    <div class="section">
                        <div class="section-title">Experiencia Laboral</div>
                        @foreach($profile->work_experience as $work)
                            <div class="item">
                                <div class="item-title">{{ $work['position'] ?? '' }}</div>
                                <div class="item-subtitle">{{ $work['company'] ?? '' }}</div>
                                <div class="item-date">
                                    {{ isset($work['start_date']) ? \Carbon\Carbon::parse($work['start_date'])->format('M Y') : '' }} 
                                    - 
                                    {{ isset($work['end_date']) ? \Carbon\Carbon::parse($work['end_date'])->format('M Y') : 'Actualidad' }}
                                </div>
                                @if(isset($work['functions']))
                                    <div class="item-description">{{ $work['functions'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @endif

                    @if($profile->references)
                    <div class="section">
                        <div class="section-title">Referencias Laborales</div>
                        @foreach($profile->references as $ref)
                            <div class="item">
                                <div class="item-title">{{ $ref['name'] ?? '' }}</div>
                                <div class="item-subtitle">{{ $ref['company'] ?? '' }}</div>
                                <div class="item-description">
                                    Tel: {{ $ref['phone'] ?? '' }} <br>
                                    Email: {{ $ref['email'] ?? '' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif

                </td>

                <!-- Sidebar Column -->
                <td class="sidebar-col">
                    
                    @if($profile->languages)
                    <div class="section">
                        <div class="section-title">Idiomas</div>
                        <table style="width: 100%;">
                        @foreach($profile->languages as $lang)
                            <tr>
                                <td style="padding: 3px 0; font-size: 12px; color: #333;">{{ $lang['language'] ?? '' }}</td>
                                <td style="padding: 3px 0; font-size: 11px; text-align: right; color: #666; font-weight: bold;">{{ $lang['level'] ?? '' }}</td>
                            </tr>
                        @endforeach
                        </table>
                    </div>
                    @endif

                    @if($profile->technical_skills)
                    <div class="section">
                        <div class="section-title">Habilidades Técnicas</div>
                        <div>
                            @foreach($profile->technical_skills as $skill)
                                <span class="skill-badge">{{ $skill['software'] ?? '' }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($profile->soft_skills)
                    <div class="section">
                        <div class="section-title">Competencias</div>
                        @foreach($profile->soft_skills as $skill)
                            <div class="soft-skill-item">• {{ $skill['skill'] ?? '' }}</div>
                        @endforeach
                    </div>
                    @endif

                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div style="position: fixed; bottom: 0; left: 0; right: 0; padding: 20px; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; background-color: #fff;">
        Generado por {{ \App\Models\Setting::first()->company_name ?? 'Cahilt Transgresoria Digital' }}
    </div>

</body>
</html>