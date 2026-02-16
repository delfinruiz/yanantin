<x-mail::message>
# Nuevo Reporte de Avance

El colaborador **{{ $employeeName }}** ha registrado un nuevo avance para el objetivo estratégico:

## {{ $objectiveTitle }}

**Fecha de Corte:** {{ $periodDate }}

<x-mail::button :url="$url">
Revisar Avance
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
