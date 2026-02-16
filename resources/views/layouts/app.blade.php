
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ config('app.name', 'Finanzas Personales') }}</title>
  @inject('settingService', 'App\Services\SettingService')
  @php
      $favicon = $settingService->get('favicon');
  @endphp
  <link rel="icon" href="{{ $favicon ? \Illuminate\Support\Facades\Storage::url($favicon) : asset('/asset/images/favicon.ico') }}">
  <link href="{{ asset('/asset/css/style.css') }}" rel="stylesheet">
  {{-- Css --}}
  @yield('css')
</head>

<body x-data="{ page: 'home', 'darkMode': true, 'stickyMenu': false, 'navigationOpen': false, 'scrollTop': false }"
  x-init="
        
        systemMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

        if (localStorage.getItem('theme') === 'system') {
          darkMode = (systemMode === 'dark') ? true : false;
        }else if (localStorage.getItem('theme') === 'dark') {
          darkMode = true;
        }else if (localStorage.getItem('theme') === 'light') {
          darkMode = false;
        }else{
          darkMode = false;
        }

        $watch('darkMode', value => localStorage.setItem('theme', value ? 'dark' : 'light'))"

  :class="{'b eh': darkMode === true}">
  <!-- ===== Header ===== -->
  @include('partials.portada.header')
  <!-- ===== Content ===== -->
  @yield('content')
  <!-- ===== Footer ===== -->
  @include('partials.portada.footer')
  
  <!-- ====== Back To Top Start ===== -->
  <button class="xc wf xf ie ld vg sr gh tr g sa ta _a" @click="window.scrollTo({top: 0, behavior: 'smooth'})"
    @scroll.window="scrollTop = (window.pageYOffset > 50) ? true : false" :class="{ 'uc' : scrollTop }">
    <svg class="uh se qd" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
      <path
        d="M233.4 105.4c12.5-12.5 32.8-12.5 45.3 0l192 192c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L256 173.3 86.6 342.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l192-192z" />
    </svg>
  </button>

  <!-- ====== Back To Top End ===== -->
  <script>
    //  Pricing Table
    const setup = () => {
      return {
        isNavOpen: false,

        billPlan: 'monthly',

        plans: [{
            name: 'Starter',
            price: {
              monthly: 29,
              annually: 29 * 12 - 199,
            },
            features: ['400 GB Storaget', 'Unlimited Photos & Videos', 'Exclusive Support'],
          },
          {
            name: 'Growth Plan',
            price: {
              monthly: 59,
              annually: 59 * 12 - 100,
            },
            features: ['400 GB Storaget', 'Unlimited Photos & Videos', 'Exclusive Support'],
          },
          {
            name: 'Business',
            price: {
              monthly: 139,
              annually: 139 * 12 - 100,
            },
            features: ['400 GB Storaget', 'Unlimited Photos & Videos', 'Exclusive Support'],
          },
        ],
      };
    };
  </script>
  <script defer src="{{ asset('/asset/js/bundle.js') }}"></script>

  {{-- Js --}}
  @yield('js')
</body>

</html>