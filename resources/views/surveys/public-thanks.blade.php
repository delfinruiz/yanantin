<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('surveys.public.thanks_title') }}</title>
    <style>
        * { box-sizing: border-box; }
        :root {
            --bg: #0b0f17;
            --text: #111827;
            --card: #ffffff;
            --border: #e5e7eb;
            --button: #1A2A4F;
            --button-text: #ffffff;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b0f17;
                --text: #e5e7eb;
                --card: #111827;
                --border: #1f2937;
                --button: #1A2A4F;
                --button-text: #ffffff;
            }
        }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial; margin: 0; padding: 16px; background: var(--bg); color: var(--text); }
        .card { max-width: 720px; margin: 0 auto; background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; text-align:center; }
        .brand { display:flex; justify-content:center; margin-bottom:10px; }
        .brand img { height: 48px; }
        .title { font-weight: 700; font-size: 20px; margin-bottom: 6px; }
        a.button { display:inline-block; padding: 10px 14px; border-radius: 8px; background: var(--button); color: var(--button-text); text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
            @if(!empty($logo))
                <img src="{{ $logo }}" alt="Logo">
            @endif
        </div>
        <div class="title">{{ __('surveys.public.thanks_header') }}</div>
        <div>{{ __('surveys.public.thanks_message', ['title' => $survey->title]) }}</div>
        <div style="margin-top:12px;">
            <a class="button" href="{{ route('home') }}">{{ __('surveys.public.back_home') }}</a>
        </div>
    </div>
</body>
</html>
