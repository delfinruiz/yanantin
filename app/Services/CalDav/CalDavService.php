<?php

namespace App\Services\CalDav;

use App\Models\EmailAccount;
use App\Models\Event;
use App\Models\Calendar;
use Illuminate\Support\Facades\Crypt;
use Sabre\DAV\Client as DavClient;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader as VObjectReader;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Services\CalDav\CalDavParser;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Storage;

class CalDavService
{
    private function endpointFor(EmailAccount $emailAccount): string
    {
        $domain = $emailAccount->domain ?? substr(strrchr($emailAccount->email, '@'), 1);
        $url = "https://{$domain}:2080/calendars/{$emailAccount->email}/calendar/";
        Log::debug("CalDAV Endpoint calculado: $url");
        return $url;
    }

    private function clientFor(EmailAccount $emailAccount): DavClient
    {
        $password = $emailAccount->decrypted_password;
        Log::debug("CalDAV Client Config: User={$emailAccount->email}, BaseURI=" . $this->endpointFor($emailAccount));
        return new DavClient([
            'baseUri' => $this->endpointFor($emailAccount),
            'userName' => $emailAccount->email,
            'password' => $password,
            'authType' => DavClient::AUTH_BASIC,
        ]);
    }

    public function discover(EmailAccount $emailAccount): array
    {
        $client = $this->clientFor($emailAccount);
        $response = $client->propFind('', [
            '{DAV:}displayname',
            '{DAV:}resourcetype',
            '{http://calendarserver.org/ns/}getctag',
            '{http://sabredav.org/ns}sync-token',
        ], 0);
        return $response ?? [];
    }

    public function createEvent(EmailAccount $emailAccount, Calendar $calendar, Event $event): array
    {
        $client = $this->clientFor($emailAccount);
        $uid = $event->caldav_uid ?: (string) Str::uuid();

        $dtStart = null;
        $dtEnd = null;
        if ($event->all_day) {
            $dtStart = $event->starts_at?->format('Ymd');
            $dtEnd = $event->ends_at?->copy()->addDay()->format('Ymd');
        } else {
            $dtStart = $event->starts_at?->setTimezone('UTC')->format('Ymd\THis\Z');
            $dtEnd = $event->ends_at?->setTimezone('UTC')->format('Ymd\THis\Z');
        }

        $eventData = [
            'UID' => $uid,
            'SUMMARY' => $event->title,
            'DESCRIPTION' => $event->description ?? '',
            'ORGANIZER' => 'MAILTO:' . $emailAccount->email,
            'DTSTAMP' => now()->setTimezone('UTC')->format('Ymd\THis\Z'),
            'CREATED' => $event->created_at?->setTimezone('UTC')->format('Ymd\THis\Z') ?? now()->setTimezone('UTC')->format('Ymd\THis\Z'),
        ];

        $vcal = new VCalendar([
            'VEVENT' => $eventData,
        ]);

        /** @var Component $vevent */
        $vevent = $vcal->VEVENT;

        if ($event->all_day) {
            if ($dtStart) {
                $vevent->add('DTSTART', $dtStart, ['VALUE' => 'DATE']);
            }
            if ($dtEnd) {
                $vevent->add('DTEND', $dtEnd, ['VALUE' => 'DATE']);
            }
        } else {
            if ($dtStart) {
                $vevent->add('DTSTART', $dtStart);
            }
            if ($dtEnd) {
                $vevent->add('DTEND', $dtEnd);
            }
        }

        if (!empty($event->attachments) && is_array($event->attachments)) {
            foreach ($event->attachments as $attachmentPath) {
                try {
                    if (Storage::disk('public')->exists($attachmentPath)) {
                        $contents = Storage::disk('public')->get($attachmentPath);
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->buffer($contents) ?: 'application/octet-stream';
                        $filename = basename($attachmentPath);

                        $vevent->add('ATTACH', $contents, [
                            'FMTTYPE' => $mime,
                            'ENCODING' => 'BASE64',
                            'VALUE' => 'BINARY',
                            'FILENAME' => $filename,
                            'X-LABEL' => $filename,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning("CalDAV: No se pudo adjuntar archivo {$attachmentPath}: " . $e->getMessage());
                }
            }
        }

        $ics = $vcal->serialize();
        $path = $uid . '.ics';

        $tries = 0;
        while (true) {
            try {
                $client->request('PUT', $path, $ics, [
                    'Content-Type' => 'text/calendar; charset=utf-8',
                ]);
                $props = $client->propFind($path, ['{DAV:}getetag'], 0);
                return ['uid' => $uid, 'etag' => $props['{DAV:}getetag'] ?? null];
            } catch (\Throwable $e) {
                $tries++;
                if ($tries >= 3) {
                    throw $e;
                }
                usleep(250000 * $tries);
            }
        }
    }

    public function updateEvent(EmailAccount $emailAccount, Calendar $calendar, Event $event): ?string
    {
        $client = $this->clientFor($emailAccount);
        $uid = $event->caldav_uid ?: (string) Str::uuid();
        $path = $uid . '.ics';

        $dtStart = null;
        $dtEnd = null;
        if ($event->all_day) {
            $dtStart = $event->starts_at?->format('Ymd');
            $dtEnd = $event->ends_at?->copy()->addDay()->format('Ymd');
        } else {
            $dtStart = $event->starts_at?->setTimezone('UTC')->format('Ymd\THis\Z');
            $dtEnd = $event->ends_at?->setTimezone('UTC')->format('Ymd\THis\Z');
        }

        $eventData = [
            'UID' => $uid,
            'SUMMARY' => $event->title,
            'DESCRIPTION' => $event->description ?? '',
            'ORGANIZER' => 'MAILTO:' . $emailAccount->email,
            'DTSTAMP' => now()->setTimezone('UTC')->format('Ymd\THis\Z'),
            'CREATED' => $event->created_at?->setTimezone('UTC')->format('Ymd\THis\Z') ?? now()->setTimezone('UTC')->format('Ymd\THis\Z'),
        ];

        $vcal = new VCalendar([
            'VEVENT' => $eventData,
        ]);

        /** @var Component $vevent */
        $vevent = $vcal->VEVENT;

        if ($event->all_day) {
            if ($dtStart) {
                $vevent->add('DTSTART', $dtStart, ['VALUE' => 'DATE']);
            }
            if ($dtEnd) {
                $vevent->add('DTEND', $dtEnd, ['VALUE' => 'DATE']);
            }
        } else {
            if ($dtStart) {
                $vevent->add('DTSTART', $dtStart);
            }
            if ($dtEnd) {
                $vevent->add('DTEND', $dtEnd);
            }
        }

        if (!empty($event->attachments) && is_array($event->attachments)) {
            foreach ($event->attachments as $attachmentPath) {
                try {
                    if (Storage::disk('public')->exists($attachmentPath)) {
                        $contents = Storage::disk('public')->get($attachmentPath);
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->buffer($contents) ?: 'application/octet-stream';
                        $filename = basename($attachmentPath);

                        $vevent->add('ATTACH', $contents, [
                            'FMTTYPE' => $mime,
                            'ENCODING' => 'BASE64',
                            'VALUE' => 'BINARY',
                            'FILENAME' => $filename,
                            'X-LABEL' => $filename,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning("CalDAV: No se pudo adjuntar archivo {$attachmentPath}: " . $e->getMessage());
                }
            }
        }

        $ics = $vcal->serialize();
        $headers = ['Content-Type' => 'text/calendar; charset=utf-8'];
        if ($event->caldav_etag) {
            $headers['If-Match'] = $event->caldav_etag;
        }

        $tries = 0;
        while (true) {
            try {
                $client->request('PUT', $path, $ics, $headers);
                $props = $client->propFind($path, ['{DAV:}getetag'], 0);
                return $props['{DAV:}getetag'] ?? null;
            } catch (\Sabre\HTTP\ClientHttpException $e) {
                $tries++;
                if ($e->getHttpStatus() === 412 && $tries < 3) {
                    $props = $client->propFind($path, ['{DAV:}getetag'], 0);
                    $headers['If-Match'] = $props['{DAV:}getetag'] ?? null;
                    continue;
                }
                if ($e->getHttpStatus() === 404) {
                    $result = $this->createEvent($emailAccount, $calendar, $event);
                    return $result['etag'] ?? null;
                }
                if ($tries >= 3) {
                    throw $e;
                }
                usleep(250000 * $tries);
            } catch (\Throwable $e) {
                $tries++;
                if ($tries >= 3) {
                    throw $e;
                }
                usleep(250000 * $tries);
            }
        }
    }

    public function deleteEvent(EmailAccount $emailAccount, Event $event): void
    {
        if (!$event->caldav_uid) {
            return;
        }
        $client = $this->clientFor($emailAccount);
        $path = $event->caldav_uid . '.ics';
        $tries = 0;
        while (true) {
            try {
                $client->request('DELETE', $path);
                return;
            } catch (\Sabre\HTTP\ClientHttpException $e) {
                if ($e->getHttpStatus() === 404) {
                    return;
                }
                $tries++;
                if ($tries >= 3) {
                    throw $e;
                }
                usleep(250000 * $tries);
            } catch (\Throwable $e) {
                $tries++;
                if ($tries >= 3) {
                    throw $e;
                }
                usleep(250000 * $tries);
            }
        }
    }

    public function listEvents(EmailAccount $emailAccount, Calendar $calendar): array
    {
        $client = $this->clientFor($emailAccount);

        $body = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<D:propfind xmlns:D="DAV:">' .
            '<D:prop><D:getetag/></D:prop>' .
            '</D:propfind>';

        $response = $client->request('PROPFIND', '', $body, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Depth' => '1',
        ]);

        $results = [];
        if (is_array($response) && isset($response['body'])) {
            $xml = $response['body'];
            // ... logging ...

            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('d', 'DAV:');
            $nodes = $xpath->query('//d:response');

            foreach ($nodes as $node) {
                $href = $xpath->query('.//d:href', $node)->item(0)?->textContent ?? null;
                $etag = $xpath->query('.//d:getetag', $node)->item(0)?->textContent ?? null;

                if (!$href) {
                    continue;
                }

                if (!str_ends_with($href, '.ics')) {
                    continue;
                }
                
                // Clean href (remove status line if present)
                $href = trim($href);
                $etag = trim($etag, '"'); // Remove quotes from ETag if present

                $results[] = [
                    'href' => $href,
                    'etag' => $etag,
                ];
            }
        }

        return $results;
    }

    public function fetchEvent(EmailAccount $emailAccount, string $href): ?array
    {
        $client = $this->clientFor($emailAccount);
        try {
            Log::debug('CalDAV fetchEvent', ['href' => $href]);
            $eventResponse = $client->request('GET', $href);
            if (!is_array($eventResponse) || !isset($eventResponse['body'])) {
                return null;
            }
            $ics = $eventResponse['body'];
            // Parse returns array of events, usually just one
            $parsedEvents = CalDavParser::parseVCalendar($ics);
            return $parsedEvents;
        } catch (\Sabre\HTTP\ClientHttpException $e) {
             if ($e->getHttpStatus() === 404) {
                Log::warning('CalDAV fetchEvent 404', ['href' => $href]);
            } else {
                Log::error('CalDAV fetchEvent error', [
                    'href' => $href,
                    'status' => $e->getHttpStatus(),
                    'message' => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('CalDAV fetchEvent error: ' . $e->getMessage(), ['href' => $href]);
        }
        return null;
    }

    /**
     * @deprecated Use listEvents and fetchEvent instead for better performance
     */
    public function syncDown(EmailAccount $emailAccount, Calendar $calendar, ?string $syncToken = null): array
    {
        $eventsMeta = $this->listEvents($emailAccount, $calendar);
        $results = [];
        foreach ($eventsMeta as $meta) {
            $parsed = $this->fetchEvent($emailAccount, $meta['href']);
            if ($parsed) {
                foreach ($parsed as $p) {
                    $p['etag'] = $meta['etag'];
                    $results[] = $p;
                }
            }
        }
        return $results;
    }

    public function syncUp(EmailAccount $emailAccount, Calendar $calendar): void
    {
        $client = $this->clientFor($emailAccount);
        $events = Event::where('calendar_id', $calendar->id)->get();
        foreach ($events as $event) {
            $shouldPush = !$event->caldav_last_sync_at || $event->updated_at > $event->caldav_last_sync_at;
            if (!$shouldPush) {
                continue;
            }
            if (!$event->caldav_uid) {
                $result = $this->createEvent($emailAccount, $calendar, $event);
                $event->caldav_uid = $result['uid'] ?? $event->caldav_uid;
                $event->caldav_etag = $result['etag'] ?? null;
                $event->caldav_last_sync_at = now();
                $event->save();
            } else {
                $etag = $this->updateEvent($emailAccount, $calendar, $event);
                if ($etag) {
                    $event->caldav_etag = $etag;
                    $event->caldav_last_sync_at = now();
                    $event->save();
                }
            }
        }
    }
}
