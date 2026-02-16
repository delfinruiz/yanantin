<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('surveys.public.access_title') }}</title>
    @if(!empty($favicon))
        <link rel="icon" href="{{ $favicon }}">
    @endif
    <style>
        * { box-sizing: border-box; }
        :root {
            --bg: #0b0f17;
            --text: #111827;
            --muted: #6b7280;
            --card: #ffffff;
            --border: #e5e7eb;
            --input: #ffffff;
            --button: #1A2A4F;
            --button-text: #ffffff;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b0f17;
                --text: #e5e7eb;
                --muted: #9ca3af;
                --card: #111827;
                --border: #1f2937;
                --input: #0b0f17;
                --button: #1A2A4F;
                --button-text: #ffffff;
            }
        }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial; margin: 0; padding: 16px; background: var(--bg); color: var(--text); }
        .card { max-width: 900px; margin: 0 auto; background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; }
        .brand { display:flex; align-items:center; gap:10px; }
        .brand img { height: 56px; }
        .brand .company { color: var(--muted); font-size:14px; }
        .title { font-weight: 700; font-size: 22px; margin-bottom: 4px; }
        .subtitle { color: var(--muted); margin-bottom: 10px; }
        .desc { color: var(--text); opacity: 0.85; font-size:14px; margin-bottom: 12px; }
        .grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
        @media (min-width: 640px) { .grid-2 { grid-template-columns: 1fr 1fr; } }
        input { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--input); color: var(--text); }
        label { display:block; margin-bottom: 6px; font-weight: 600; color: var(--muted); }
        button { padding: 10px 14px; border-radius: 10px; border: none; background: var(--button); color: var(--button-text); cursor: pointer; }
        .muted { font-size: 12px; color: var(--muted); }
        .qr { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .qr img { width: 160px; height: 160px; border-radius: 8px; border: 1px solid var(--border); }
        .row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand" style="margin-bottom:10px;">
            @if(!empty($logo))
                <img src="{{ $logo }}" alt="Logo">
            @endif
        </div>
        <div class="title">{{ $survey->title }}</div>
        @if(!empty($survey->description))
            <div class="desc">{{ $survey->description }}</div>
        @endif
        <form method="POST" action="{{ route('surveys.public.start', ['token' => $token]) }}" class="grid">
            @csrf
            <div>
                <label>{{ __('surveys.public.full_name') }}</label>
                <input type="text" name="name" required maxlength="255" placeholder="{{ __('surveys.public.name_placeholder') }}">
                @error('name')<div class="muted" style="color:#ef4444">{{ $message }}</div>@enderror
            </div>
            <div>
                <label>{{ __('surveys.public.email') }}</label>
                <input type="email" name="email" required maxlength="255" placeholder="{{ __('surveys.public.email_placeholder') }}">
                @error('email')<div class="muted" style="color:#ef4444">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <button type="submit">{{ __('surveys.public.start') }}</button>
                <button type="button" onclick="shareLink()">{{ __('surveys.public.share') }}</button>
            </div>
        </form>
        <div style="margin-top:12px;">
            <div class="subtitle">{{ __('surveys.public.qr_code') }}</div>
            <div class="qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode($link) }}&size=250x250" alt="QR">
                <div class="grid">
                    <a href="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode($link) }}&size=500x500" download="qr-{{ $survey->id }}.png">{{ __('surveys.public.download_png') }}</a>
                    <a href="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode($link) }}&format=svg&size=500x500" download="qr-{{ $survey->id }}.svg">{{ __('surveys.public.download_svg') }}</a>
                </div>
            </div>
            <div class="muted">{{ __('surveys.public.qr_help') }}</div>
        </div>
    </div>
    <script id="survey-data" type="application/json">
        {!! json_encode(['shareText' => __('surveys.public.share_text', ['title' => $survey->title]), 'url' => $link]) !!}
    </script>
    <script>
        function shareLink() {
            const data = JSON.parse(document.getElementById('survey-data').textContent);
            const url = data.url;
            const text = data.shareText;
            if (navigator.share) {
                navigator.share({ title: 'Encuesta', text, url }).catch(() => {})
            } else {
                window.location.href = 'mailto:?subject=Encuesta&body=' + encodeURIComponent(text + '\n' + url)
            }
        }
    </script>
</body>
</html>
