@php
    $tokens = $theme ? $theme->tokens : [
        'colors' => ['primary' => '#288cfa', 'secondary' => '#103766', 'danger' => '#ef4444', 'success' => '#2E865F', 'gray' => '#64748b', 'background' => '#ffffff', 'text' => '#1e293b'],
        'fonts' => ['base' => 'system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, sans-serif'],
        'spacing' => ['sm' => '0.5rem','md' => '1rem','lg' => '1.5rem'],
        'radius' => ['sm' => '0.25rem','md' => '0.5rem','lg' => '0.75rem'],
    ];

    $button = $def->button ?? [
        'label' => __('formbuilder.submit'),
        'bg_color' => $tokens['colors']['primary'] ?? '#288cfa',
        'text_color' => '#ffffff',
    ];

    $primary = $tokens['colors']['primary'] ?? '#288cfa';
    $secondary = $tokens['colors']['secondary'] ?? '#103766';
    $text = $tokens['colors']['text'] ?? '#1e293b';
    $bg = $tokens['colors']['background'] ?? '#ffffff';
    $danger = $tokens['colors']['danger'] ?? '#ef4444';
    $success = $tokens['colors']['success'] ?? '#2E865F';
    $gray = $tokens['colors']['gray'] ?? '#64748b';
    $fontBase = $tokens['fonts']['base'] ?? 'system-ui, sans-serif';
    $spaceSm = $tokens['spacing']['sm'] ?? '0.5rem';
    $spaceMd = $tokens['spacing']['md'] ?? '1rem';
    $spaceLg = $tokens['spacing']['lg'] ?? '1.5rem';
    $radiusMd = $tokens['radius']['md'] ?? '0.5rem';

    // Button colors logic
    $btnBg = $theme ? $primary : ($button['bg_color'] ?? $primary);
    $btnText = $theme ? '#ffffff' : ($button['text_color'] ?? '#ffffff');

    // Google Fonts Mapping
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
        --btn-bg: {{ $btnBg }};
        --btn-text: {{ $btnText }};

        /* Light Mode Defaults */
        --bg-body: transparent; 
        --bg-container: {{ $bg }};
        --border-container: #e2e8f0; 
        --text-title: {{ $text }};
        --text-label: {{ $text }};
        --bg-input: #ffffff;
        --border-input: #cbd5e1;
        --text-input: #0f172a;
        --text-muted: #64748b;
        --bg-error: #fee2e2;
        --text-error: #b91c1c;
        --border-error: #fecaca;
    }

    /* Dark Mode Support */
    @media (prefers-color-scheme: dark) {
        :root {
            --bg-body: transparent;
            --bg-container: #1e293b; 
            --border-container: #334155;
            --text-title: #f1f5f9;
            --text-label: #e2e8f0;
            --bg-input: #334155;
            --border-input: #475569;
            --text-input: #f1f5f9;
            --color-secondary: #f8fafc;
            --text-muted: #94a3b8;
            --bg-error: #450a0a;
            --text-error: #fecaca;
            --border-error: #7f1d1d;
        }
    }

    * { box-sizing: border-box; }
    body { font-family: var(--font-base); margin: 0; background-color: var(--bg-body); color: var(--text-input); }
    /* Container fills iframe */
    .fb-container {
        width: 100%;
        padding: var(--space-md);
        background-color: var(--bg-container);
        color: var(--text-input);
        border: 1px solid var(--border-container);
        border-radius: var(--radius-md);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    .fb-title { color: var(--text-title); margin-bottom: var(--space-md); margin-top: 0; }
    .fb-field { margin-bottom: var(--space-md); }
    .fb-label { display:block; font-weight:600; margin-bottom: .4rem; color: var(--text-label); }
    .fb-input, .fb-select, .fb-textarea { width:100%; padding:.6rem .8rem; border:1px solid var(--border-input); border-radius: var(--radius-md); background-color: var(--bg-input); color: var(--text-input); box-shadow: 0 1px 2px rgba(0,0,0,0.08); }
    .fb-input:focus, .fb-select:focus, .fb-textarea:focus { outline: 2px solid var(--color-primary); outline-offset: -1px; border-color: var(--color-primary); }
    .fb-btn { padding:.6rem 1rem; border:0; border-radius: var(--radius-md); cursor:pointer; background-color: var(--btn-bg); color: var(--btn-text); font-weight: 500; box-shadow: 0 4px 10px rgba(0,0,0,0.12); display: inline-flex; align-items: center; justify-content: center; gap: .5rem; }
    .fb-btn:hover { filter: brightness(0.95); }
    .fb-btn[disabled] { opacity: .75; cursor: not-allowed; }
    .fb-btn.loading { opacity: 1; pointer-events: none; }
    .fb-btn.loading::before { content: ""; width: 1.125rem; height: 1.125rem; border: 2px solid var(--btn-text); border-top-color: transparent; border-radius: 50%; display: inline-block; animation: fbspin .6s linear infinite; }
    @keyframes fbspin { to { transform: rotate(360deg); } }
    .text-danger { color: var(--text-error); font-size: 0.875rem; margin-top: 0.25rem; }
    </style>
    <script>
    (function () {
        function sendHeight() {
            var body = document.body || document.documentElement;
            var html = document.documentElement;
            var height = Math.max(
                body.scrollHeight,
                body.offsetHeight,
                html.clientHeight,
                html.scrollHeight,
                html.offsetHeight
            );
            try {
                window.parent.postMessage(
                    { type: 'formbuilder:resize', id: '{{ $def->id }}', height: height },
                    '*'
                );
            } catch (e) {}
        }
        document.addEventListener('DOMContentLoaded', function () {
            sendHeight();
            var target = document.body || document.documentElement;
            var observer = new MutationObserver(function () {
                sendHeight();
            });
            observer.observe(target, { childList: true, subtree: true });
        });
        window.addEventListener('resize', sendHeight);
        window.addEventListener('load', sendHeight);
    })();
    </script>
    <script>
    (function () {
        function init() {
            var form = document.querySelector('form');
            if (!form) return;
            form.setAttribute('method', 'POST');
            form.setAttribute('enctype', 'multipart/form-data');
            var btn = form.querySelector('.fb-btn');
            if (!btn) return;
            form.addEventListener('submit', function () {
                btn.classList.add('loading');
                btn.setAttribute('disabled', 'disabled');
                var label = btn.querySelector('.fb-btn-label');
                if (label) {
                    label.textContent = btn.dataset.processing || 'Procesando...';
                }
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>
</head>
<body>
<div class="fb-container">
    <h1 class="fb-title">{{ $def->name }}</h1>
    @if ($errors->any())
        <div style="background-color: var(--bg-error); color: var(--text-error); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid var(--border-error);">
            <ul style="margin: 0; padding-left: 1.5rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('forms.submit', ['id' => $def->id]) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_method" value="POST">
        @foreach($def->elements as $el)
            @php
                $type = $el['type'] ?? 'text';
                $name = $el['name'] ?? 'field_' . $loop->index;
                $label = $el['label'] ?? ucfirst($name);
                $inputName = str_replace([' ', '.'], '_', $name);
                $props = $el['props'] ?? [];
                $validations = $el['validations'] ?? [];
                $isRequired = ($validations['required'] ?? null);
            @endphp
            <div class="fb-field">
                <label class="fb-label">
                    {{ $label }}
                    @if($isRequired === true || $isRequired === 1 || $isRequired === '1')
                        <span class="text-danger">*</span>
                    @endif
                </label>
                @if($type === 'textarea')
                    <textarea name="{{ $inputName }}" class="fb-textarea" rows="{{ $props['rows'] ?? 4 }}">{{ old($inputName) }}</textarea>
                @elseif($type === 'select')
                    <select name="{{ $inputName }}" class="fb-select">
                        <option value="">{{ __('formbuilder.select_option') }}</option>
                        @foreach(($props['options'] ?? []) as $opt)
                            @php
                                $val = is_array($opt) ? ($opt['value'] ?? '') : $opt;
                                $lab = is_array($opt) ? ($opt['label'] ?? '') : $opt;
                            @endphp
                            <option value="{{ $val }}" {{ old($inputName) == $val ? 'selected' : '' }}>{{ $lab }}</option>
                        @endforeach
                    </select>
                @elseif($type === 'radio')
                    @foreach(($props['options'] ?? []) as $opt)
                        @php
                            $val = is_array($opt) ? ($opt['value'] ?? '') : $opt;
                            $lab = is_array($opt) ? ($opt['label'] ?? '') : $opt;
                        @endphp
                        <label style="margin-right: 1rem;"><input type="radio" name="{{ $inputName }}" value="{{ $val }}" {{ old($inputName) == $val ? 'checked' : '' }}> {{ $lab }}</label>
                    @endforeach
                @elseif($type === 'checkbox')
                    <input type="checkbox" name="{{ $inputName }}" value="1" {{ old($inputName) ? 'checked' : '' }}>
                @elseif($type === 'date' || $type === 'datetime')
                    <input type="{{ $type === 'datetime' ? 'datetime-local' : 'date' }}" name="{{ $inputName }}" class="fb-input" value="{{ old($inputName) }}">
                @elseif($type === 'file')
                    <input type="file" name="{{ $inputName }}" class="fb-input">
                @else
                    <input type="{{ $type }}" name="{{ $inputName }}" class="fb-input" placeholder="{{ $props['placeholder'] ?? '' }}" value="{{ old($inputName) }}">
                @endif
                @error($inputName)
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        @endforeach
        <button class="fb-btn" type="submit" data-processing="{{ __('formbuilder.processing') }}"><span class="fb-btn-label">{{ $button['label'] ?? __('formbuilder.submit') }}</span></button>
    </form>
</div>
</body>
</html>
