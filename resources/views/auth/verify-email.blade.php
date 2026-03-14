@extends('layouts.app')

@section('content')
<style>
    .verify-section {
        --verify-bg: #f3f4f6;
        --verify-card-bg: #ffffff;
        --verify-text-main: #111827;
        --verify-text-secondary: #4b5563;
        --verify-border: #e5e7eb;
        --verify-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --verify-btn-bg: #2563eb;
        --verify-btn-hover: #1d4ed8;
        --verify-btn-text: #ffffff;
        --verify-success-bg: #f0fdf4;
        --verify-success-text: #15803d;
        --verify-success-border: #bbf7d0;
        --verify-error-bg: #fef2f2;
        --verify-error-text: #b91c1c;
        --verify-error-border: #fecaca;
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding-top: 120px; /* Espacio para el header fijo */
        padding-bottom: 60px;
        background-color: var(--verify-bg);
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .b .verify-section,
    .dark .verify-section {
        --verify-bg: #111827;
        --verify-card-bg: #1f2937;
        --verify-text-main: #f9fafb;
        --verify-text-secondary: #d1d5db;
        --verify-border: #374151;
        --verify-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        --verify-success-bg: rgba(20, 83, 45, 0.3);
        --verify-success-text: #86efac;
        --verify-success-border: rgba(34, 197, 94, 0.2);
        --verify-error-bg: rgba(127, 29, 29, 0.3);
        --verify-error-text: #fca5a5;
        --verify-error-border: rgba(248, 113, 113, 0.2);
    }

    .verify-container {
        width: 100%;
        max-width: 28rem; /* max-w-md */
        padding-left: 1rem;
        padding-right: 1rem;
        position: relative;
        z-index: 10;
    }

    .verify-card {
        background-color: var(--verify-card-bg);
        border-radius: 1rem; /* rounded-2xl */
        padding: 2.5rem; /* p-10 */
        box-shadow: var(--verify-shadow);
        border: 1px solid var(--verify-border);
        text-align: center;
    }

    .verify-icon {
        margin-bottom: 1.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 50%;
        background-color: rgba(37, 99, 235, 0.1);
        color: #3b82f6;
    }

    .verify-title {
        font-size: 1.5rem; /* text-2xl */
        font-weight: 700;
        color: var(--verify-text-main);
        margin-bottom: 0.75rem;
        line-height: 1.2;
    }

    .verify-desc {
        font-size: 0.95rem;
        color: var(--verify-text-secondary);
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .verify-alert {
        background-color: var(--verify-success-bg);
        color: var(--verify-success-text);
        border: 1px solid var(--verify-success-border);
        padding: 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        margin-bottom: 1.5rem;
        text-align: left;
    }

    .verify-alert-error {
        background-color: var(--verify-error-bg);
        color: var(--verify-error-text);
        border: 1px solid var(--verify-error-border);
        padding: 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        margin-bottom: 1.5rem;
        text-align: left;
    }

    .verify-btn-primary {
        display: inline-flex;
        width: 100%;
        justify-content: center;
        align-items: center;
        padding: 0.75rem 1.5rem;
        background-color: var(--verify-btn-bg);
        color: var(--verify-btn-text);
        font-weight: 600;
        font-size: 0.95rem;
        border-radius: 0.5rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
        text-decoration: none;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }

    .verify-btn-primary:hover {
        background-color: var(--verify-btn-hover);
    }

    .verify-btn-link {
        display: inline-flex;
        margin-top: 1.5rem;
        color: var(--verify-text-secondary);
        font-size: 0.875rem;
        background: none;
        border: none;
        cursor: pointer;
        text-decoration: underline;
        transition: color 0.2s;
    }

    .verify-btn-link:hover {
        color: var(--verify-text-main);
    }
</style>

<section class="verify-section">
    <div class="verify-container">
        <div class="verify-card">
            <!-- Icono decorativo -->
            <div class="verify-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>

            <h2 class="verify-title">{{ __('Verifica tu correo') }}</h2>
            
            <p class="verify-desc">
                {{ __('Gracias por registrarte. Hemos enviado un enlace de confirmación a tu correo electrónico. Por favor revísalo para activar tu cuenta.') }}
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="verify-alert">
                    <strong>{{ __('¡Enviado!') }}</strong> {{ __('Un nuevo enlace de verificación ha sido enviado a tu dirección de correo electrónico.') }}
                </div>
            @endif
            
            @if (session('status') == 'verification-link-failed')
                <div class="verify-alert-error">
                    <strong>{{ __('No se pudo enviar el correo') }}</strong>
                    {{ __('En este momento no pudimos enviar el enlace de verificación. Intenta nuevamente en unos minutos o contacta al soporte.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="verify-btn-primary">
                    {{ __('Reenviar correo de verificación') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="verify-btn-link">
                    {{ __('Cerrar sesión') }}
                </button>
            </form>
        </div>
    </div>
</section>
@endsection
