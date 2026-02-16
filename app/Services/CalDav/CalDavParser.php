<?php

namespace App\Services\CalDav;

use Sabre\VObject\Reader as VObjectReader;
use Illuminate\Support\Carbon;

class CalDavParser
{
    public static function parseVCalendar(string $ics): array
    {
        $result = [];
        $vcal = VObjectReader::read($ics);
        foreach ($vcal->select('VEVENT') as $vevent) {
            $attachmentsMeta = [];
            foreach ($vevent->select('ATTACH') as $attach) {
                $value = (string) $attach;
                $mime = isset($attach['FMTTYPE']) ? (string) $attach['FMTTYPE'] : null;
                $filename = isset($attach['FILENAME']) ? (string) $attach['FILENAME'] : null;
                if (!$filename && isset($attach['X-LABEL'])) {
                    $filename = (string) $attach['X-LABEL'];
                }
                $encoding = isset($attach['ENCODING']) ? strtoupper((string) $attach['ENCODING']) : null;
                $valueType = isset($attach['VALUE']) ? strtoupper((string) $attach['VALUE']) : null;

                $binaryBase64 = null;
                $uri = null;

                if ($encoding === 'BASE64' || $valueType === 'BINARY') {
                    $raw = $attach->getValue();
                    $binaryBase64 = base64_encode($raw);
                } else {
                    $uri = $value;
                }

                $attachmentsMeta[] = [
                    'data' => $binaryBase64,
                    'uri' => $uri,
                    'mime' => $mime,
                    'filename' => $filename,
                ];
            }
            $uid = (string) $vevent->UID;
            $summary = (string) $vevent->SUMMARY;
            $description = isset($vevent->DESCRIPTION) ? (string) $vevent->DESCRIPTION : null;
            $starts = isset($vevent->DTSTART) ? Carbon::parse((string) $vevent->DTSTART) : null;
            $ends = isset($vevent->DTEND) ? Carbon::parse((string) $vevent->DTEND) : null;
            
            // Check explicit VALUE=DATE parameter first
            $isDate = isset($vevent->DTSTART['VALUE']) && (string)$vevent->DTSTART['VALUE'] === 'DATE';
            
            // Fallback: check if time is 00:00:00
            $allDay = $isDate || ($starts && $starts->format('H:i:s') === '00:00:00' && (!$ends || $ends->format('H:i:s') === '00:00:00'));
            
            if ($allDay && $ends) {
                $ends = $ends->copy()->subDay();
            }
            $rrule = isset($vevent->{'RRULE'}) ? (string) $vevent->{'RRULE'} : null;
            $alarms = [];
            foreach ($vevent->select('VALARM') as $alarm) {
                $alarms[] = [
                    'action' => isset($alarm->ACTION) ? (string) $alarm->ACTION : null,
                    'trigger' => isset($alarm->TRIGGER) ? (string) $alarm->TRIGGER : null,
                    'description' => isset($alarm->DESCRIPTION) ? (string) $alarm->DESCRIPTION : null,
                ];
            }
            $hasAttachments = count($attachmentsMeta) > 0;
            $result[] = [
                'uid' => $uid,
                'title' => $summary,
                'description' => $description,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'all_day' => $allDay,
                'rrule' => $rrule,
                'alarms' => $alarms,
                'has_attachments' => $hasAttachments,
                'attachments_meta' => $attachmentsMeta,
            ];
        }
        return $result;
    }
}
