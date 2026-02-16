<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuesta</title>
    @inject('settingService', 'App\Services\SettingService')
    @php
        $favicon = $settingService->get('favicon');
        $logoLight = $settingService->get('logo_light');
        $logoDark = $settingService->get('logo_dark');
        $logoLightUrl = $logoLight ? \Illuminate\Support\Facades\Storage::url($logoLight) : asset('/asset/images/logo-light.png');
        $logoDarkUrl = $logoDark ? \Illuminate\Support\Facades\Storage::url($logoDark) : asset('/asset/images/logo-dark.png');
    @endphp
    <link rel="icon" href="{{ $favicon ? \Illuminate\Support\Facades\Storage::url($favicon) : asset('/asset/images/favicon.ico') }}">
    <style>
        :root { color-scheme: light dark; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica Neue, Arial; }
        .card { max-width: 640px; width: 100%; border: 1px solid #e5e7eb; border-radius: .75rem; padding: 2rem; text-align: center; }
        .title { font-size: 1.5rem; font-weight: 700; margin-top: .75rem; }
        .subtitle { font-size: .95rem; color: #6b7280; margin-top: .5rem; }
        .logo { max-height: 110px; height: auto; }
        .actions { margin-top: 1.25rem; display:flex; gap:.75rem; justify-content:center; }
        .btn { display:inline-block; border-radius:.5rem; padding:.5rem .875rem; font-weight:500; border:1px solid #9ca3af; text-decoration:none; }
        .btn-primary { background:#3b82f6; color:#fff; border-color:#2563eb; }
        .btn-primary:hover { background:#2563eb; }
        .btn-secondary { background:transparent; color:#374151; }
        @media (prefers-color-scheme: dark) {
            .card { border-color:#374151; }
            .subtitle { color:#9ca3af; }
            .btn-secondary { color:#e5e7eb; border-color:#4b5563; }
        }
    </style>
</head>
<body>
    <div class="card" role="status" aria-live="polite">
        <picture>
            <source srcset="{{ $logoDarkUrl }}" media="(prefers-color-scheme: dark)">
            <img src="{{ $logoLightUrl }}" alt="Logo" class="logo">
        </picture>
        <div class="title">
            @if(($status ?? '') === 'already')
                Encuesta ya contestada
            @else
                ¡Gracias por su respuesta!
            @endif
        </div>
        <div class="subtitle">
            {{ $survey->title }}
        </div>
        <div class="actions">
            <a href="{{ \App\Filament\Pages\MySurveys::getUrl() }}" class="btn">Mis encuestas</a>
            <a href="#" class="btn btn-primary" onclick="window.close(); return false;">Cerrar pestaña</a>
        </div>
    </div>
</body>
</html>

