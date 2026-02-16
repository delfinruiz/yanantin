@php
    $tokens = $theme ? $theme->tokens : [
        'colors' => ['primary' => '#288cfa', 'secondary' => '#103766', 'danger' => '#ef4444', 'success' => '#2E865F', 'gray' => '#64748b', 'background' => '#ffffff', 'text' => '#1e293b', 'page' => '#f8fafc'],
        'fonts' => ['base' => 'system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, sans-serif'],
        'spacing' => ['sm' => '0.5rem','md' => '1rem','lg' => '1.5rem'],
        'radius' => ['sm' => '0.25rem','md' => '0.5rem','lg' => '0.75rem'],
    ];
    $favicon = app(\App\Services\SettingService::class)->get('favicon');
    $faviconUrl = $favicon ? \Illuminate\Support\Facades\Storage::disk('public')->url($favicon) : null;

    $primary = $tokens['colors']['primary'] ?? '#288cfa';
    $secondary = $tokens['colors']['secondary'] ?? '#103766';
    $danger = $tokens['colors']['danger'] ?? '#ef4444';
    $success = $tokens['colors']['success'] ?? '#2E865F';
    $gray = $tokens['colors']['gray'] ?? '#64748b';
    $text = $tokens['colors']['text'] ?? '#1e293b';
    $bg = $tokens['colors']['background'] ?? '#ffffff';
    $pageBg = $tokens['colors']['page'] ?? '#f8fafc';
    $fontBase = $tokens['fonts']['base'] ?? 'system-ui, sans-serif';
    $spaceSm = $tokens['spacing']['sm'] ?? '0.5rem';
    $spaceMd = $tokens['spacing']['md'] ?? '1rem';
    $spaceLg = $tokens['spacing']['lg'] ?? '1.5rem';
    $radiusMd = $tokens['radius']['md'] ?? '0.5rem';

    $googleFonts = [
        'Roboto' => 'Roboto:wght@300;400;500;700',
        'Open Sans' => 'Open+Sans:wght@300;400;500;600;700',
        'Lato' => 'Lato:wght@300;400;700',
        'Montserrat' => 'Montserrat:wght@300;400;500;600;700',
        'Raleway' => 'Raleway:wght@300;400;500;600;700',
        'Poppins' => 'Poppins:wght@300;400;500;600;700',
        'Merriweather' => 'Merriweather:wght@300;400;700;900',
        'Playfair Display' => 'Playfair+Display:wght@400;500;600;700',
    ];
    $googleFontUrl = null;
    foreach ($googleFonts as $key => $family) {
        if (str_contains($fontBase, $key)) {
            $googleFontUrl = "https://fonts.googleapis.com/css2?family={$family}&display=swap";
            break;
        }
    }
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gracias - {{ $def->name }}</title>
    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    @if($googleFontUrl)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{{ $googleFontUrl }}" rel="stylesheet">
    @endif
    <style>
        :root {
            --color-primary: {{ $primary }};
            --color-secondary: {{ $secondary }};
            --color-danger: {{ $danger }};
            --color-success: {{ $success }};
            --color-gray: {{ $gray }};
            --font-base: {!! $fontBase !!};
            --space-sm: {{ $spaceSm }};
            --space-md: {{ $spaceMd }};
            --space-lg: {{ $spaceLg }};
            --radius-md: {{ $radiusMd }};
            
            /* Light Mode Defaults */
            --bg-body: {{ $pageBg }};
            --bg-container: {{ $bg }};
            --border-container: #e5e7eb;
            --text-title: {{ $success }};
            --text-message: {{ $text }};
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-body: #0f172a;
                --bg-container: #1e293b;
                --border-container: #334155;
                --text-title: #4ade80;
                --text-message: #e2e8f0;
            }
        }

        * { box-sizing: border-box; }
        body { 
            font-family: var(--font-base); 
            margin: 0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            background-color: var(--bg-body);
            transition: background-color 0.3s;
        }
        .fb-container { 
            max-width: 480px; 
            width: 100%; 
            padding: var(--space-lg); 
            border: 1px solid var(--border-container); 
            border-radius: var(--radius-md); 
            text-align: center; 
            background: var(--bg-container); 
            position: relative;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .fb-title { color: var(--text-title); margin-bottom: var(--space-md); }
        .fb-message { color: var(--text-message); margin-bottom: var(--space-lg); }
        .fb-btn { background: var(--color-primary); color:#fff; padding:.6rem 1rem; border:0; border-radius: var(--radius-md); cursor:pointer; text-decoration: none; display: inline-block; }
        .fb-btn:hover { filter: brightness(0.95); }
    </style>
</head>
<body>
<div class="fb-container">
    <h1 class="fb-title">¡Gracias!</h1>
    <p class="fb-message">Su respuesta ha sido registrada correctamente.</p>
    
</div>
</body>
</html>
