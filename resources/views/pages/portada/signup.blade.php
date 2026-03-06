@extends('layouts.app')

@section('content')
  <main
    x-data="signupForm()"
    x-init="init()"
    data-initial-name='@json(old("name", ""))'
    data-initial-email='@json(old("email", ""))'
    data-initial-password='@json(old("password", ""))'
    data-initial-password-confirmation='@json(old("password_confirmation", ""))'
  >
    <section class="i pg fh rm ki xn vq gj qp gr hj rp hr relative overflow-hidden">
      <img src="{{ asset('/asset/images/portada/shape-06.svg') }}" alt="Shape" class="h j k shape-left-fix" />
      <img src="{{ asset('/asset/images/portada/shape-03.svg') }}" alt="Shape" class="h l m" />
      <img src="{{ asset('/asset/images/portada/shape-17.svg') }}" alt="Shape" class="h n o" />
      <img src="{{ asset('/asset/images/portada/shape-18.svg') }}" alt="Shape" class="h p q shape-bottom-right-fix" />

      <div class="animate_top bb af i va sg hh sm vk xm yi _n jp hi ao kp signup-card">
        <span class="rc h r s zd/2 od zg gh"></span>
        <span class="rc h r q zd/2 od xg mh"></span>

        <div class="rj">
          <h2 class="ek ck kk wm xb">Crea tu cuenta</h2>
          <p class="sj hk xj">Completa los campos para registrarte.</p>
        </div>

        <form class="sb" action="{{ route('signup.store') }}" method="POST" x-on:submit="handleSubmit($event)">
          @csrf

          <div class="signup-grid">
            <div class="wb signup-field">
              <label class="rc kk wm vb" for="name">Nombre completo</label>
              <input
                type="text"
                name="name"
                id="name"
                x-model.trim="name"
                value="{{ old('name') }}"
                placeholder="Tu nombre"
                class="vd hh rg zk _g ch hm dm fm pl/50 xi mi sm xm pm dn/40"
                required
              />
            </div>

            <div class="wb signup-field">
              <label class="rc kk wm vb" for="email">Correo electrónico</label>
              <input
                type="email"
                name="email"
                id="email"
                value="{{ old('email') }}"
                placeholder="example@gmail.com"
                x-model.trim="email"
                x-on:input="validateEmail()"
                :class="[
                  'vd hh rg zk _g ch hm dm fm pl/50 xi mi sm xm pm dn/40',
                  emailTouched && !emailValid ? 'border border-red-500' : '',
                  emailValid ? 'border border-emerald-500' : ''
                ]"
                required
              />
            </div>

            <div class="wb signup-field">
              <label class="rc kk wm vb" for="password">Contraseña</label>
              <input
                type="text"
                name="password"
                id="password"
                placeholder="Debe cumplir con los Requisitos"
                value="{{ old('password') }}"
                x-model="password"
                x-on:input="validatePassword()"
                :class="[
                  'vd hh rg zk _g ch hm dm fm pl/50 xi mi sm xm pm dn/40',
                  passwordTouched && !passwordValid ? 'border border-red-500' : '',
                  passwordValid ? 'border border-emerald-500' : ''
                ]"
                autocomplete="new-password"
                required
              />
            </div>

            <div class="wb signup-field">
              <label class="rc kk wm vb" for="password_confirmation">Confirmar contraseña</label>
              <input
                type="text"
                name="password_confirmation"
                id="password_confirmation"
                placeholder="**************"
                value="{{ old('password_confirmation') }}"
                x-model="passwordConfirmation"
                x-on:input="validatePasswordConfirmation()"
                :class="[
                  'vd hh rg zk _g ch hm dm fm pl/50 xi mi sm xm pm dn/40',
                  passwordConfirmationTouched && !passwordsMatch ? 'border border-red-500' : '',
                  passwordsMatch && passwordConfirmation.length > 0 ? 'border border-emerald-500' : ''
                ]"
                autocomplete="new-password"
                required
              />
            </div>

            <div class="signup-password-row">
              <div class="signup-password-cell">
                <span :style="passwordLengthOk ? 'color:#34d399' : 'color:#f87171'">
                  • Al menos 8 caracteres
                </span>
              </div>
              <div class="signup-password-cell">
                <span :style="passwordHasUpper ? 'color:#34d399' : 'color:#f87171'">
                  • Al menos una letra mayúscula
                </span>
              </div>
              <div class="signup-password-cell">
                <span :style="passwordHasNumber ? 'color:#34d399' : 'color:#f87171'">
                  • Al menos un número
                </span>
              </div>
              <div class="signup-password-cell">
                <span :style="passwordHasSymbol ? 'color:#34d399' : 'color:#f87171'">
                  • Al menos un símbolo
                </span>
              </div>
            </div>
          </div>

          <div
            class="signup-form-messages"
            x-cloak
            x-show="(emailTouched && !emailValid) || (passwordConfirmationTouched && !passwordsMatch) || {{ $errors->any() ? 'true' : 'false' }}"
          >
            <p
              x-show="emailTouched && !emailValid"
              x-cloak
              class="signup-message signup-message-error"
            >
              Ingresa un correo electrónico válido.
            </p>
            <p
              x-show="passwordConfirmationTouched && !passwordsMatch"
              x-cloak
              class="signup-message signup-message-error"
            >
              Las contraseñas no coinciden.
            </p>
            @if ($errors->any())
              <p class="signup-message signup-message-error">
                {{ $errors->first() }}
              </p>
            @endif
          </div>

          <button
            class="signup-submit vd rj ek rc rg gh lk ml il _l gi hi flex items-center justify-center"
            type="submit"
            :disabled="!canSubmit || isProcessing"
            :class="{
              'opacity-40 cursor-not-allowed': !canSubmit || isProcessing,
              'cursor-pointer': canSubmit && !isProcessing
            }"
          >
            <span x-show="!isProcessing">Registrarme</span>
            <span x-show="isProcessing" x-cloak class="flex items-center justify-center">
              <span class="signup-spinner"></span>
            </span>
          </button>

          <p class="sj hk xj rj ob">
            ¿Ya tienes una cuenta?
            <a class="mk" href="{{ route('filament.admin.auth.login') }}"> Iniciar sesión </a>
          </p>
        </form>
      </div>
    </section>
  </main>
  <script>
    function signupForm() {
      return {
        name: '',
        email: '',
        password: '',
        passwordConfirmation: '',
        emailValid: false,
        emailTouched: false,
        passwordValid: false,
        passwordTouched: false,
        passwordLengthOk: false,
        passwordHasUpper: false,
        passwordHasNumber: false,
        passwordHasSymbol: false,
        passwordConfirmationTouched: false,
        passwordsMatch: false,
        isProcessing: false,

        get canSubmit() {
          return this.name.trim().length > 0
            && this.emailValid
            && this.passwordValid
            && this.passwordsMatch;
        },

        init() {
          try {
            this.name = JSON.parse(this.$el.dataset.initialName || '""');
            this.email = JSON.parse(this.$el.dataset.initialEmail || '""');
            this.password = JSON.parse(this.$el.dataset.initialPassword || '""');
            this.passwordConfirmation = JSON.parse(this.$el.dataset.initialPasswordConfirmation || '""');
          } catch (e) {
            this.name = '';
            this.email = '';
            this.password = '';
            this.passwordConfirmation = '';
          }
          if (this.email && this.email.length > 0) {
            this.emailTouched = true;
            this.validateEmail();
          }
          if (this.password && this.password.length > 0) {
            this.passwordTouched = true;
            this.validatePassword();
          }
          if (this.passwordConfirmation && this.passwordConfirmation.length > 0) {
            this.passwordConfirmationTouched = true;
            this.validatePasswordConfirmation();
          }
        },

        validateEmail() {
          this.emailTouched = true;
          const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          this.emailValid = pattern.test(this.email);
        },

        validatePassword() {
          this.passwordTouched = true;
          const value = this.password || '';
          this.passwordLengthOk = value.length >= 8;
          this.passwordHasUpper = /[A-Z]/.test(value);
          this.passwordHasNumber = /[0-9]/.test(value);
          this.passwordHasSymbol = /[^A-Za-z0-9]/.test(value);
          this.passwordValid = this.passwordLengthOk && this.passwordHasUpper && this.passwordHasNumber && this.passwordHasSymbol;
          this.validatePasswordConfirmation();
        },

        validatePasswordConfirmation() {
          this.passwordConfirmationTouched = true;
          this.passwordsMatch = this.password.length > 0 && this.password === this.passwordConfirmation;
        },

        handleSubmit(event) {
          if (!this.canSubmit || this.isProcessing) {
            event.preventDefault();
            this.validateEmail();
            this.validatePassword();
            this.validatePasswordConfirmation();
            return;
          }

          this.isProcessing = true;
        },
      };
    }
  </script>
  <style>
    .signup-card {
      width: 100%;
      max-width: 840px;
    }

    .signup-grid {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .signup-field {
      margin-bottom: 0;
    }

    @media (min-width: 768px) {
      .signup-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 1.5rem;
        row-gap: 1rem;
      }
    }

    .signup-password-row {
      grid-column: 1 / -1;
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      column-gap: 1.5rem;
      row-gap: 0.25rem;
      font-size: 0.85rem;
      margin-top: -0.25rem;
      margin-bottom: 0.75rem;
    }

    .signup-password-cell span {
      display: inline-block;
    }

    .signup-form-messages {
      text-align: center;
      font-size: 0.9rem;
      margin-top: -0.25rem;
      margin-bottom: 0.75rem;
      padding: 0.5rem 0.75rem;
      /* Light mode */
      background: rgba(15, 23, 42, 0.06);
      border: 1px solid rgba(15, 23, 42, 0.12);
      border-radius: 0.75rem;
      box-shadow: 0 1px 2px rgba(0,0,0,0.06), inset 0 0 0 1px rgba(0,0,0,0.02);
    }
    .dark .signup-form-messages {
      /* Dark mode override */
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 1px 2px rgba(0,0,0,0.08), inset 0 0 0 1px rgba(255,255,255,0.02);
    }

    .signup-message {
      margin: 0.15rem 0;
    }

    .signup-message-error {
      color: #f87171;
    }

    .signup-submit[disabled] {
      background-image: none;
      background-color: #4b5563;
    }

    /* Shapes positioning to match login page behaviour */
    .shape-left-fix {
      left: 0 !important;
    }
    .shape-bottom-right-fix {
      position: fixed !important;
      bottom: 0 !important;
      right: 0 !important;
      z-index: 0;
    }

    .signup-spinner {
      width: 20px;
      height: 20px;
      border: 2px solid rgba(255, 255, 255, 0.35);
      border-top-color: #ffffff;
      border-radius: 50%;
      animation: signup-spin .6s linear infinite;
      display: inline-block;
    }
    @keyframes signup-spin {
      to {
        transform: rotate(360deg);
      }
    }
  </style>
@endsection
