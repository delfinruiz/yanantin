<div class="fb-preview-wrapper p-4 overflow-hidden relative"
    x-data="{
        mode: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
        toggle() {
            this.mode = this.mode === 'dark' ? 'light' : 'dark';
        }
    }"
>
    @php
        $state = is_array($this->data ?? null) ? $this->data : [];
        $elements = $state['elements'] ?? [];
        $formName = $state['name'] ?? __('formbuilder.untitled_form');
        
        // Find selected theme
        $themeId = $state['themeId'] ?? null;
        $availableThemes = $themes ?? $this->themes ?? [];
        $theme = null;
        if ($themeId && !empty($availableThemes)) {
             $theme = collect($availableThemes)->firstWhere('id', $themeId);
        }
 
        $tokens = is_array($theme) ? ($theme['tokens'] ?? []) : [];
        
        // Extract token values with defaults (Light Mode Base)
        $primary = $tokens['colors']['primary'] ?? '#288cfa';
        $secondary = $tokens['colors']['secondary'] ?? '#103766';
        $text = $tokens['colors']['text'] ?? '#1e293b';
        $bg = $tokens['colors']['background'] ?? '#ffffff';
        $page = $tokens['colors']['page'] ?? '#f8fafc';
        
        $radius = $tokens['radius']['md'] ?? '0.5rem';
        $font = $tokens['fonts']['base'] ?? 'sans-serif';
        
        $buttonData = $state['button'] ?? [];
        $buttonLabel = is_array($buttonData) ? ($buttonData['label'] ?? __('formbuilder.submit')) : __('formbuilder.submit');
        
        // If a theme is selected, use theme colors. Otherwise fallback to button specific colors or primary.
        $btnBg = $theme ? $primary : (is_array($buttonData) ? ($buttonData['bg_color'] ?? $primary) : $primary);
        $btnText = $theme ? '#ffffff' : (is_array($buttonData) ? ($buttonData['text_color'] ?? '#ffffff') : '#ffffff');

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
            if (str_contains($font, $key)) {
                $googleFontUrl = "https://fonts.googleapis.com/css2?family={$family}&display=swap";
                break;
            }
        }
    @endphp

    @if($googleFontUrl)
        <link rel="stylesheet" href="{{ $googleFontUrl }}">
    @endif

    <style>
        .fb-theme-scope {
            /* CSS Variables - Light Mode Defaults (from Theme) */
            --fb-bg-container: {{ $bg }};
            --fb-text-main: {{ $text }};
            --fb-primary: {{ $primary }};
            --fb-btn-bg: {{ $btnBg }};
            --fb-btn-text: {{ $btnText }};
            --fb-radius: {{ $radius }};
            --fb-font: {!! $font !!};
            --fb-page-bg: {{ $page }};
            
            --fb-bg-input: #ffffff;
            --fb-border-input: #e5e7eb;
            --fb-text-input: #1a1a1a;
            --fb-border-container: #e2e8f0;
            --fb-ring-color: {{ $primary }};
            --fb-text-muted: #64748b;
        }

        /* Dark Mode Overrides */
        .fb-theme-scope.dark {
            --fb-bg-container: #1e293b;
            --fb-text-main: #f1f5f9;
            --fb-bg-input: #334155;
            --fb-border-input: #475569;
            --fb-text-input: #f1f5f9;
            --fb-border-container: #334155;
            --fb-text-muted: #94a3b8;
            /* Keep primary color but maybe adjust if needed, currently keeping same */
        }

        .fb-container {
            background-color: var(--fb-bg-container);
            color: var(--fb-text-main);
            border-radius: var(--fb-radius);
            font-family: var(--fb-font);
            border: 1px solid var(--fb-border-container);
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }
        .fb-preview-wrapper {
            background-color: var(--fb-page-bg);
        }

        .fb-preview-btn {
            background-color: var(--fb-btn-bg) !important;
            color: var(--fb-btn-text) !important;
            border-radius: var(--fb-radius);
            transition: background-color 0.2s;
        }

        .fb-field label {
            color: var(--fb-text-main);
        }

        .fb-field input, .fb-field select, .fb-field textarea {
            border-radius: var(--fb-radius);
            background-color: var(--fb-bg-input);
            color: var(--fb-text-input);
            border: 1px solid var(--fb-border-input);
            width: 100%;
            padding: 0.5rem 0.75rem;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }
        
        /* File Input Styling */
        .fb-file-input {
            width: 100%;
            color: var(--fb-text-muted);
            font-size: 0.875rem;
        }
        .fb-file-input::file-selector-button {
            margin-right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px; /* Rounded full */
            border: 0;
            font-size: 0.875rem;
            font-weight: 600;
            background-color: var(--fb-primary);
            color: #ffffff;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .fb-file-input::file-selector-button:hover {
            opacity: 0.9;
        }

        .fb-field input:focus, .fb-field select:focus, .fb-field textarea:focus {
             --tw-ring-color: var(--fb-ring-color);
             border-color: var(--fb-ring-color);
             outline: 2px solid var(--fb-ring-color);
             outline-offset: -1px;
        }

        .fb-title {
            color: var(--fb-text-main);
        }

        .fb-radio, .fb-checkbox {
            color: var(--fb-primary);
        }
        
        /* Toggle Button Styles */
        .theme-toggle-btn {
            background: transparent;
            border: 1px solid var(--fb-border-input);
            color: var(--fb-text-main);
            padding: 0.5rem;
            border-radius: 9999px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .theme-toggle-btn:hover {
            background-color: var(--fb-bg-input);
        }
    </style>

    <div class="fb-theme-scope" :class="{ 'dark': mode === 'dark' }">
        <div class="fb-container p-6 shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold fb-title">{{ $formName }}</h2>
                
                <!-- Theme Toggle Button -->
                <button 
                    type="button" 
                    @click="toggle()" 
                    class="theme-toggle-btn"
                    title="{{ __('formbuilder.toggle_mode') }}"
                >
                    <!-- Sun Icon (Show when Dark) -->
                    <svg x-show="mode === 'dark'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                    <!-- Moon Icon (Show when Light) -->
                    <svg x-show="mode === 'light'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                </button>
            </div>
            
            <form onsubmit="return false;" class="space-y-4">
                @foreach($elements as $el)
                    @php
                        $type = $el['type'] ?? 'text';
                        $label = $el['label'] ?? __('formbuilder.no_label_field');
                        $props = $el['props'] ?? [];
                        $placeholder = $props['placeholder'] ?? '';
                        $options = $props['options'] ?? [];
                        // Handle repeater options format which is array of arrays in Filament builder
                        if (is_array($options) && isset($options[0]['label'])) {
                             $newOpts = [];
                             foreach($options as $o) {
                                 if(isset($o['label']) && isset($o['value'])) {
                                     $newOpts[$o['label']] = $o['value'];
                                 }
                             }
                             $options = $newOpts;
                        }
                        $validations = $el['validations'] ?? [];
                        $isRequired = ($validations['required'] ?? null);
                    @endphp

                    <div class="fb-field">
                        <label class="block font-medium mb-1">
                            {{ $label }}
                            @if($isRequired === true || $isRequired === 1 || $isRequired === '1')
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        
                        @if($type === 'textarea')
                            <textarea class="shadow-sm focus:outline-none focus:ring-1" rows="{{ $props['rows'] ?? 3 }}" placeholder="{{ $placeholder }}"></textarea>
                        
                        @elseif($type === 'select')
                            <select class="shadow-sm focus:outline-none focus:ring-1">
                                <option value="">{{ __('formbuilder.select_option') }}</option>
                                @foreach($options as $optLabel => $optValue)
                                    <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                @endforeach
                            </select>
                        
                        @elseif($type === 'radio')
                            <div class="space-y-2">
                                @foreach($options as $optLabel => $optValue)
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="preview_{{ $loop->parent->index }}" class="form-radio fb-radio">
                                        <span class="ml-2">{{ $optLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        
                        @elseif($type === 'checkbox')
                            <label class="inline-flex items-center">
                                <input type="checkbox" class="form-checkbox fb-checkbox">
                                <span class="ml-2">{{ $label }}</span>
                            </label>
                        
                        @elseif($type === 'file')
                            <input type="file" class="fb-file-input">
                        
                        @elseif($type === 'date' || $type === 'datetime')
                             <input type="{{ $type === 'datetime' ? 'datetime-local' : 'date' }}" class="shadow-sm focus:outline-none focus:ring-1">
                        
                        @else
                            <input type="{{ $type }}" class="shadow-sm focus:outline-none focus:ring-1" placeholder="{{ $placeholder }}">
                        @endif
                    </div>
                @endforeach
                
                <button
                    type="button"
                    class="fb-preview-btn px-4 py-2 font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                >
                    {{ $buttonLabel }}
                </button>
            </form>
        </div>
    </div>
</div>
