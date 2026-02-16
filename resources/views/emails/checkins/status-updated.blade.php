<x-mail::message>
# Actualización de Estado de Revisión

Tu reporte de avance para el objetivo **{{ $objectiveTitle }}** ha sido actualizado.

**Nuevo Estado:** 
@switch($status)
    @case('approved')
        <span style="color: green; font-weight: bold;">Aprobado</span>
        @break
    @case('rejected_with_correction')
        <span style="color: red; font-weight: bold;">Rechazado (Requiere Corrección)</span>
        @break
    @case('incumplido')
        <span style="color: red; font-weight: bold;">Incumplido</span>
        @break
    @default
        {{ $status }}
@endswitch

**Revisado por:** {{ $reviewerName }}

@if($comment)
**Comentarios:**
> {{ $comment }}
@endif

<x-mail::button :url="$url">
Ver Detalles
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
