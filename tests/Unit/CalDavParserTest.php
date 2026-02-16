<?php

use App\Services\CalDav\CalDavParser;
use Illuminate\Support\Carbon;

test('parse VEVENT with RRULE and VALARM', function () {
    $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//EN
BEGIN:VEVENT
UID:12345-abc
SUMMARY:Reunión semanal
DESCRIPTION:Sincronización de equipo
DTSTART:20260114T100000Z
DTEND:20260114T110000Z
RRULE:FREQ=WEEKLY;BYDAY=MO
BEGIN:VALARM
ACTION:DISPLAY
DESCRIPTION:Recordatorio de reunión
TRIGGER:-PT15M
END:VALARM
END:VEVENT
END:VCALENDAR
ICS;
    $events = CalDavParser::parseVCalendar($ics);
    expect($events)->toHaveCount(1);
    $e = $events[0];
    expect($e['uid'])->toBe('12345-abc');
    expect($e['title'])->toBe('Reunión semanal');
    expect($e['description'])->toBe('Sincronización de equipo');
    expect($e['rrule'])->toBe('FREQ=WEEKLY;BYDAY=MO');
    expect($e['alarms'][0]['action'])->toBe('DISPLAY');
    expect($e['alarms'][0]['trigger'])->toBe('-PT15M');
});

