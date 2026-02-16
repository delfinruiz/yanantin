<x-mail::message>
# Meeting Canceled

The meeting **{{ $topic }}** scheduled for **{{ $startTime->format('F j, Y, g:i a') }}** has been canceled.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
