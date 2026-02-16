<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('surveys.public.respond_title') }}</title>
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
        .card { max-width: 960px; margin: 0 auto; background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; }
        .brand { display:flex; align-items:center; gap:10px; }
        .brand img { height: 48px; }
        .title { font-weight: 700; font-size: 22px; margin-bottom: 12px; }
        .section { margin-top: 18px; border: 1px solid var(--border); border-radius: 14px; }
        .section-title { padding: 12px 16px; font-weight: 700; font-size: 16px; color: var(--text); background: rgba(0,0,0,0.03); border-bottom: 1px solid var(--border); border-top-left-radius: 14px; border-top-right-radius: 14px; }
        .section-body { padding: 12px 16px; }
        .q { margin-top: 12px; padding: 12px; border: 1px solid var(--border); border-radius: 12px; }
        label { display:block; margin-bottom: 8px; font-weight: 600; color: var(--muted); }
        input, select, textarea { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--input); color: var(--text); }
        .row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        button { padding: 10px 14px; border-radius: 10px; border: none; background: var(--button); color: var(--button-text); cursor: pointer; }
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
            <div style="color: var(--text); opacity: 0.85; font-size:14px; margin-bottom: 12px;">{{ $survey->description }}</div>
        @endif
        <form method="POST" action="{{ route('surveys.public.submit', ['token' => $token]) }}">
            @csrf
            @foreach($groups as $item => $qs)
                <div class="section">
                    <div class="section-title">{{ $item === 'Sección' ? __('surveys.public.section') : $item }}</div>
                    <div class="section-body">
                        @foreach($qs as $q)
                            <div class="q">
                                <label>{{ $q->content }} @if($q->required)<span style="color:#f59e0b">*</span>@endif</label>
                                @php $name = 'q_'.$q->id; @endphp
                                @if($q->type === 'text')
                                    <textarea name="{{ $name }}" rows="3" @if($q->required) required @endif></textarea>
                                @elseif($q->type === 'bool')
                                    <select name="{{ $name }}" @if($q->required) required @endif>
                                        <option value="">{{ __('surveys.public.select') }}</option>
                                        <option value="si">{{ __('surveys.public.yes') }}</option>
                                        <option value="no">{{ __('surveys.public.no') }}</option>
                                    </select>
                                @elseif($q->type === 'scale_5')
                                    <input type="number" name="{{ $name }}" min="0" max="5" step="1" @if($q->required) required @endif>
                                @elseif($q->type === 'scale_10')
                                    <input type="number" name="{{ $name }}" min="0" max="10" step="1" @if($q->required) required @endif>
                                @elseif($q->type === 'likert')
                                    <select name="{{ $name }}" @if($q->required) required @endif>
                                        <option value="">{{ __('surveys.public.select') }}</option>
                                        <option value="1">{{ __('surveys.public.strongly_disagree') }}</option>
                                        <option value="2">{{ __('surveys.public.disagree') }}</option>
                                        <option value="3">{{ __('surveys.public.neutral') }}</option>
                                        <option value="4">{{ __('surveys.public.agree') }}</option>
                                        <option value="5">{{ __('surveys.public.strongly_agree') }}</option>
                                    </select>
                                @elseif($q->type === 'multi')
                                    @php
                                        $opts = [];
                                        if (is_string($q->options)) {
                                            $opts = json_decode($q->options, true) ?? [];
                                        } elseif (is_array($q->options)) {
                                            $opts = $q->options;
                                        }
                                    @endphp
                                    <div class="row">
                                        @foreach($opts as $k => $label)
                                            <label style="display:flex; align-items:center; gap:6px;">
                                                <input type="checkbox" name="{{ $name }}[]" value="{{ $k }}">
                                                <span>{{ is_string($label) ? $label : $k }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <input type="text" name="{{ $name }}" @if($q->required) required @endif>
                                @endif
                                @error($name)<div style="color:#ef4444;">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div class="row" style="margin-top:12px;">
                <button type="submit">Enviar respuestas</button>
            </div>
        </form>
    </div>
</body>
</html>
