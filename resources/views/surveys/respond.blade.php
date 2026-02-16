<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder encuesta</title>
    @inject('settingService', 'App\Services\SettingService')
    @php
        $favicon = $settingService->get('favicon');
        $logoLight = $settingService->get('logo_light');
        $logoDark = $settingService->get('logo_dark');
        $logoLightUrl = $logoLight ? \Illuminate\Support\Facades\Storage::url($logoLight) : asset('/asset/images/logo-light.png');
        $logoDarkUrl = $logoDark ? \Illuminate\Support\Facades\Storage::url($logoDark) : asset('/asset/images/logo-dark.png');
    @endphp
    <link rel="icon" href="{{ $favicon ? \Illuminate\Support\Facades\Storage::url($favicon) : asset('/asset/images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica Neue, Arial, 'Apple Color Emoji', 'Segoe UI Emoji'; }
        .container { max-width: 900px; margin: 0 auto; padding: 1.5rem; }
        .title { font-size: 1.25rem; font-weight: 600; }
        .subtitle { font-size: .875rem; color: #6b7280; }
        .section { border: 1px solid #e5e7eb; border-radius: .5rem; padding: 1rem; margin-top: 1rem; }
        .label { font-size: .875rem; font-weight: 500; display: block; margin-bottom: .5rem; }
        .input, select, textarea { width: 100%; border: 1px solid #d1d5db; border-radius: .375rem; padding: .5rem; }
        .btn { display: inline-block; border-radius: .5rem; padding: .5rem .875rem; cursor: pointer; font-weight: 500; transition: background-color .2s, color .2s, border-color .2s; text-decoration: none; }
        .btn-primary { background: #3b82f6; color: #ffffff; border: 1px solid #2563eb; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: transparent; color: #374151; border: 1px solid #9ca3af; text-decoration: none; }
        .btn-secondary:hover { background: #f3f4f6; }
        .btn-secondary:focus, .btn-primary:focus { outline: 2px solid #60a5fa; outline-offset: 2px; }
        .btn-link { color: #3b82f6; text-decoration: none; font-size: .875rem; }
        .msg-error { color: #ef4444; font-size: .875rem; margin-top: .75rem; }
        .msg-ok { color: #16a34a; font-size: .875rem; margin-top: .75rem; }
        .logo-wrap { display:flex; align-items:center; justify-content:center; margin: 1rem 0; }
        .logo { max-height: 100px; height: auto; }
        select[multiple] { min-height: 140px; line-height: 1.4; }
        select, textarea, input[type="text"] { background-color: #ffffff; color: #111827; }
        select option { color: #111827; }
        @media (prefers-color-scheme: dark) {
            .subtitle { color: #9ca3af; }
            .section { border-color: #374151; }
            select, textarea, input[type="text"] { background-color: #111827; border-color: #374151; color: #e5e7eb; }
            select option { color: #e5e7eb; }
            .btn-secondary { color: #e5e7eb; border-color: #4b5563; }
            .btn-secondary:hover { background: #1f2937; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-wrap" aria-label="Logo">
            <picture>
                <source srcset="{{ $logoDarkUrl }}" media="(prefers-color-scheme: dark)">
                <img src="{{ $logoLightUrl }}" alt="Logo" class="logo">
            </picture>
        </div>
        <div style="display:flex;align-items:center;justify-content:flex-start;gap:1rem;">
            <h2 class="title">{{ $survey->title }}</h2>
        </div>
        <p class="subtitle">{{ $survey->description }}</p>
        @if(session('error'))
            <div class="msg-error">{{ session('error') }}</div>
        @endif
        @if(session('message'))
            <div class="msg-ok">{{ session('message') }}</div>
        @endif
        <form method="POST" action="{{ route('surveys.respond.submit', $survey) }}" style="margin-top:1rem;">
            @csrf
            @foreach($questions->groupBy('item') as $item => $qs)
                <div class="section">
                    <h3 style="font-weight:500;">{{ $item }}</h3>
                    <div style="margin-top:.5rem;">
                        @foreach($qs as $q)
                            <div style="margin-bottom:1rem;">
                                <label class="label">{{ $q->content }} @if($q->required) * @endif</label>
                                @php $key = 'q_'.$q->id; $val = $state[$key] ?? null; @endphp
                                @if($q->type === 'text')
                                    <textarea name="{{ $key }}" rows="3" class="input">{{ old($key, $val) }}</textarea>
                                @elseif($q->type === 'bool' || $q->type === 'boolean' || $q->type === 'vf' || $q->type === 'true_false')
                                    <div class="radio-group" role="radiogroup" aria-label="{{ $q->content }}">
                                        <label style="display:inline-flex;align-items:center;gap:.5rem;margin-right:1rem;">
                                            <input type="radio" name="{{ $key }}" value="si" @if(in_array(old($key,$val), ['si','1'])) checked @endif @if($q->required) required @endif>
                                            <span>Sí</span>
                                        </label>
                                        <label style="display:inline-flex;align-items:center;gap:.5rem;">
                                            <input type="radio" name="{{ $key }}" value="no" @if(in_array(old($key,$val), ['no','0'])) checked @endif @if($q->required) required @endif>
                                            <span>No</span>
                                        </label>
                                    </div>
                                @elseif($q->type === 'scale_5')
                                    <select name="{{ $key }}" class="input">
                                        @foreach(['0','1','2','3','4','5'] as $o)
                                            <option value="{{ $o }}" @if(old($key,$val)===$o) selected @endif>{{ $o }}</option>
                                        @endforeach
                                    </select>
                                @elseif($q->type === 'scale_10')
                                    <select name="{{ $key }}" class="input">
                                        @for($i=0;$i<=10;$i++)
                                            <option value="{{ $i }}" @if(old($key,$val)===(string)$i) selected @endif>{{ $i }}</option>
                                        @endfor
                                    </select>
                                @elseif($q->type === 'likert')
                                    @php $options = $q->options ?? ['1'=>'Nunca','2'=>'Casi nunca','3'=>'A veces','4'=>'Casi siempre','5'=>'Siempre']; @endphp
                                    <select name="{{ $key }}" class="input">
                                        @foreach($options as $ov => $ol)
                                            <option value="{{ $ov }}" @if(old($key,$val)===(string)$ov) selected @endif>{{ $ol }}</option>
                                        @endforeach
                                    </select>
                                @elseif($q->type === 'multi')
                                    @php $options = $q->options ?? []; $selected = is_array($val) ? $val : (json_decode($val,true) ?: []); @endphp
                                    <select name="{{ $key }}[]" multiple class="input">
                                        @foreach($options as $ov => $ol)
                                            <option value="{{ $ov }}" @if(in_array($ov, old($key,$selected))) selected @endif>{{ $ol }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" name="{{ $key }}" value="{{ old($key,$val) }}" class="input">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary">Guardar respuestas</button>
            </div>
        </form>
    </div>
</body>
</html>
