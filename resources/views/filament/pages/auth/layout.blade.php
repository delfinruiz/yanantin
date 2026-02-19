@php
    $settingService = app(\App\Services\SettingService::class);

    $rawLogoLight = $settingService->get('logo_light');
    $rawLogoDark = $settingService->get('logo_dark');
    $rawFavicon = $settingService->get('favicon');
    $company_name = $settingService->get('company_name', config('app.name'));

    $logo_light = $rawLogoLight ? \Illuminate\Support\Facades\Storage::url($rawLogoLight) : asset('asset/images/logo-light.png');
    $logo_dark = $rawLogoDark ? \Illuminate\Support\Facades\Storage::url($rawLogoDark) : asset('asset/images/logo-dark.png');
    $favicon = $rawFavicon ? \Illuminate\Support\Facades\Storage::url($rawFavicon) : asset('favicon.ico');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ __('Iniciar sesión') }} | {{ $company_name }}</title>
    <link rel="icon" href="{{ $favicon }}">
    @filamentStyles
    {{ filament()->getTheme()->getHtml() }}
    <link href="{{ asset('css/custom-login.css') }}" rel="stylesheet">
    
    <style>
        [x-cloak] { display: none !important; }

        :root {
            --login-bg: #f8fafc;
            --login-border: #e5e7eb;
            --login-text: #0f172a;
            --login-icon: #6b7280;
        }
        .dark {
            --login-bg: rgb(24 28 49);
            --login-border: rgb(60 69 86);
            --login-text: #ffffff;
            --login-icon: #9ca3af;
        }

        /* Autofill Fix: Prevent background color change */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px transparent inset !important;
            -webkit-text-fill-color: var(--login-text) !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .fi-input::placeholder {
            color: var(--login-icon) !important;
            opacity: 1 !important;
        }
        .fi-input {
            caret-color: var(--login-text) !important;
        }

        /* Ensure shapes touch edges */
        .shape-left-fix {
            left: 0 !important;
        }
        .shape-bottom-right-fix {
            position: fixed !important;
            bottom: 0 !important;
            right: 0 !important;
            z-index: 0;
        }
        
        /* Shape 17 Fix */
        .n {
            bottom: 0% !important;
        }

        /* Checkbox Fix: Reset styles to look like a checkbox */
        input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 1.25rem !important;
            height: 1.25rem !important;
            border: 1px solid var(--login-border) !important;
            border-radius: 0.25rem !important;
            background-color: transparent !important;
            display: inline-block !important;
            position: relative !important;
            margin-right: 0.5rem !important;
            cursor: pointer;
            padding: 0 !important;
        }

        input[type="checkbox"]:checked {
            background-color: #288cfa !important;
            border-color: #288cfa !important;
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            background-size: 100% !important;
        }

        /* Wrapper Reset for proper input positioning */
        .fi-input-wrp {
            display: flex !important;
            align-items: center !important;
            position: relative !important;
            box-shadow: none !important;
            border-width: 1px !important;
            border-style: solid !important;
            background-color: var(--login-bg) !important;
            border-color: var(--login-border) !important;
        }

        /* Input Styling */
        .fi-input {
            color: var(--login-text) !important;
        }

        /* Password Toggle Button Position (Eye Icon) - Targeting the suffix container and button */
        .fi-input-wrp .fi-input-suffix,
        .fi-input-wrp .fi-input-element-suffix {
            /* Reset absolute positioning, let flexbox handle it */
            position: static !important;
            transform: none !important;
            background-color: transparent !important;
            border: none !important;
            color: var(--login-icon) !important;
            z-index: 20 !important;
            margin: 0 !important;
            padding: 0 1rem 0 0 !important; /* Right padding inside wrapper */
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: auto !important;
            width: auto !important;
        }

        .fi-input-wrp button[title="Show password"],
        .fi-input-wrp button[title="Hide password"] {
            background-color: transparent !important;
            border: none !important;
            color: var(--login-icon) !important;
            margin: 0 !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* Ensure the icon SVG itself is sized correctly */
        .fi-input-wrp .fi-input-suffix svg,
        .fi-input-wrp button svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
        }


    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body x-data="{ page: 'signin', darkMode: false, stickyMenu: false, navigationOpen: false, scrollTop: false }"
  x-init="
        const systemMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        const theme = localStorage.getItem('theme');
        if (theme === 'system') {
            darkMode = (systemMode === 'dark');
        } else if (theme === 'dark') {
            darkMode = true;
        } else if (theme === 'light') {
            darkMode = false;
        } else {
            darkMode = false;
        }
        $watch('darkMode', value => {
            localStorage.setItem('theme', value ? 'dark' : 'light');
            if (value) document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
        });
        if (darkMode) document.documentElement.classList.add('dark');
        else document.documentElement.classList.remove('dark');
        "
  :class="{'b eh': darkMode === true}">

  @php
      // Settings logic moved to top of file
  @endphp

  <!-- ===== Header Start ===== -->
  @include('partials.portada.header')
  <!-- ===== Header End ===== -->

  <main>
    <!-- ===== SignIn Form Start ===== -->
    <section class="i pg fh rm ki xn vq gj qp gr hj rp hr relative overflow-hidden"
        x-data
        x-init="
            const addClasses = () => {
                // Checkboxes handling
                document.querySelectorAll('input[type=checkbox]').forEach(el => {
                    el.classList.remove('vd', 'hh', 'rg', 'zk', '_g', 'ch', 'hm', 'dm', 'fm', 'pl/50', 'xi', 'mi', 'sm', 'xm', 'pm', 'dn/40');
                });

                // Inputs and Selects handling
                document.querySelectorAll('input:not([type=checkbox]), select').forEach(el => {
                    const wrapper = el.closest('.fi-input-wrp');
                    
                    if (wrapper) {
                        // Apply template visual classes to the wrapper instead of the input
                        wrapper.classList.add('vd', 'hh', 'rg', 'zk', '_g', 'ch', 'hm', 'dm', 'fm', 'pl/50', 'mi', 'sm', 'xm', 'pm', 'dn/40');
                        
                        wrapper.classList.add('xi');
                        
                        // Force consistent height for all inputs
                        wrapper.style.height = '3.25rem';
                        wrapper.style.minHeight = '3.25rem';
                        wrapper.style.paddingTop = '0';
                        wrapper.style.paddingBottom = '0';

                        // Reset wrapper styles to ensure flexbox works
                        wrapper.style.display = 'flex';
                        wrapper.style.alignItems = 'center';
                        wrapper.style.position = 'relative';
                        
                        // Reset input styles to be transparent and fill available space
                        el.style.backgroundColor = 'transparent';
                        el.style.border = 'none';
                        el.style.boxShadow = 'none';
                        el.style.outline = 'none';
                        el.style.width = '100%';
                        el.style.height = '100%';
                        el.style.flex = '1';
                        el.style.paddingRight = '0'; // Remove padding, wrapper/suffix handles it
                        el.style.margin = '0';
                        
                        // Ensure the wrapper overrides standard Filament wrapper styles
                        wrapper.style.boxShadow = 'none';
                        wrapper.style.borderWidth = '1px';
                        wrapper.style.borderStyle = 'solid';
                    } else {
                         // Fallback for inputs without Filament wrapper
                         el.classList.add('vd', 'hh', 'rg', 'zk', '_g', 'ch', 'hm', 'dm', 'fm', 'pl/50', 'xi', 'mi', 'sm', 'xm', 'pm', 'dn/40');
                         el.style.backgroundColor = ''; 
                         el.style.color = '';
                         el.style.borderColor = '';
                    }
                });
            };
            addClasses();
            // Re-apply on Livewire updates
            Livewire.hook('morph.updated', () => addClasses());
        "
    >
        <!-- Bg Shapes -->
            <img src="{{ asset('images/login-theme/shape-06.svg') }}" alt="Shape" class="h j k shape-left-fix" />
            <img src="{{ asset('images/login-theme/shape-03.svg') }}" alt="Shape" class="h l m" />
            <img src="{{ asset('images/login-theme/shape-17.svg') }}" alt="Shape" class="h n o" />
            <img src="{{ asset('images/login-theme/shape-18.svg') }}" alt="Shape" class="h p q shape-bottom-right-fix" />

        <div class="animate_top bb af i va sg hh sm vk xm yi _n jp hi ao kp">
            <!-- Bg Border -->
            <span class="rc h r s zd/2 od zg gh"></span>
            <span class="rc h r q zd/2 od xg mh"></span>

            <div class="rj">
                <h2 class="ek ck kk wm xb">{{ __('Iniciar sesión') }}</h2>
                <p>{{ __('Inicia sesión para continuar') }}</p>
            </div>

            <div class="sb">
                {{ $slot }}
            </div>
        </div>
    </section>
    <!-- ===== SignIn Form End ===== -->
  </main>

  @include('partials.portada.footer')
  
  @filamentScripts
  @vite('resources/js/app.js')
</body>
</html>
