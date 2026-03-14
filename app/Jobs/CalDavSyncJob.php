<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\EmailAccount;
use App\Models\Calendar;
use App\Models\Event;
use App\Services\CalDav\CalDavService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalDavSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public function __construct(public int $userId) {}

    private function storeRemoteAttachments(array $attachmentsMeta, string $uid): ?array
    {
        if (empty($attachmentsMeta)) {
            return null;
        }

        $paths = [];

        foreach ($attachmentsMeta as $index => $meta) {
            if (empty($meta['data'])) {
                continue;
            }

            $binary = base64_decode($meta['data'], true);
            if ($binary === false) {
                continue;
            }

            $extension = null;
            if (!empty($meta['mime'])) {
                $parts = explode('/', $meta['mime'], 2);
                if (count($parts) === 2 && !empty($parts[1])) {
                    $extension = $parts[1];
                }
            }

            if (!$extension && !empty($meta['filename']) && str_contains($meta['filename'], '.')) {
                $extension = pathinfo($meta['filename'], PATHINFO_EXTENSION);
            }

            if (!$extension) {
                $extension = 'bin';
            }

            $baseName = $uid . '_' . $index;
            if (!empty($meta['filename'])) {
                $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $meta['filename']);
                $baseName = $uid . '_' . $index . '_' . $safeName;
            }

            $fileName = $baseName . '.' . $extension;
            $path = 'event-attachments/' . $fileName;

            Storage::disk('public')->put($path, $binary);
            $paths[] = $path;
        }

        if (empty($paths)) {
            return null;
        }

        return $paths;
    }

    public function handle(): void
    {
        $startTime = time();
        $emailAccount = EmailAccount::where('user_id', $this->userId)->first();
        if (!$emailAccount || empty($emailAccount->encrypted_password)) {
            return;
        }

        $service = new CalDavService();
        // Only sync to the PRIMARY personal calendar to avoid duplicates.
        // We assume the first created personal calendar is the "default" one.
        $defaultCalendar = Calendar::where('user_id', $this->userId)
            ->where('is_personal', true)
            ->orderBy('id')
            ->first();

        if (!$defaultCalendar) {
            return;
        }

        $stopProcessing = false;

        Log::info("CalDavSyncJob: Sincronizando usuario {$this->userId} usando calendario primario {$defaultCalendar->id}");
        
        // Fetch remote events ONCE for the account
        $remoteEventsMeta = $service->listEvents($emailAccount, $defaultCalendar);
        Log::info("CalDavSyncJob: Encontrados " . count($remoteEventsMeta) . " eventos remotos (metadatos)");

        // Optimization: Fetch all local events for THIS USER (across all personal calendars) to check for existence
        // This prevents creating a duplicate in Default Calendar if it already exists in "Entrevistas"
        $allUserPersonalEventsMap = Event::join('calendars', 'events.calendar_id', '=', 'calendars.id')
            ->where('calendars.user_id', $this->userId)
            ->where('calendars.is_personal', true)
            ->whereNotNull('events.caldav_href')
            ->select(['events.id', 'events.calendar_id', 'events.caldav_href', 'events.caldav_etag', 'events.caldav_uid', 'events.updated_at', 'events.caldav_last_sync_at', 'events.attachments'])
            ->get()
            ->groupBy('caldav_href');

        $processedUids = []; // Track UIDs processed to handle deletions later
        $completed = true;

        foreach ($remoteEventsMeta as $meta) {
            // Time limit check (stop after 45 seconds)
            if (time() - $startTime > 45) {
                $stopProcessing = true;
                $completed = false;
                break;
            }

            $href = $meta['href'];
            $etag = $meta['etag'];

            // Check if we have ANY local event with same href and etag
            if (isset($allUserPersonalEventsMap[$href])) {
                $locals = $allUserPersonalEventsMap[$href];
                $first = $locals->first();
                
                // If the first matching local event has the same ETag, we assume no changes.
                // We add its UID to processed list and continue.
                // Note: If we have duplicates, we just check the first one.
                if ($first && $first->caldav_etag === $etag) {
                    foreach ($locals as $local) {
                        if ($local->caldav_uid) {
                            $processedUids[] = $local->caldav_uid;
                        }
                    }
                    continue;
                }
            }

            // Fetch content
            $parsedEvents = $service->fetchEvent($emailAccount, $href);
            if (!$parsedEvents) {
                continue;
            }

            foreach ($parsedEvents as $re) {
                $processedUids[] = $re['uid'];
                
                if (time() - $startTime > 50) {
                        $stopProcessing = true;
                        $completed = false;
                        break 2;
                }

                // Adjust Timezone
                if (!$re['all_day']) {
                    $appTimezone = config('app.timezone');
                    if (!empty($re['starts_at']) && $re['starts_at'] instanceof \Illuminate\Support\Carbon) {
                        $re['starts_at'] = $re['starts_at']->setTimezone($appTimezone);
                    }
                    if (!empty($re['ends_at']) && $re['ends_at'] instanceof \Illuminate\Support\Carbon) {
                        $re['ends_at'] = $re['ends_at']->setTimezone($appTimezone);
                    }
                }

                Log::debug("CalDavSyncJob: Procesando evento remoto UID {$re['uid']}", ['title' => $re['title']]);
                try {
                    // Find local event in ANY personal calendar
                    $local = Event::join('calendars', 'events.calendar_id', '=', 'calendars.id')
                        ->where('calendars.user_id', $this->userId)
                        ->where('calendars.is_personal', true)
                        ->where('events.caldav_uid', $re['uid'])
                        ->select('events.*')
                        ->first();
                    
                    if (!$local) {
                        Log::info("CalDavSyncJob: Creando nuevo evento local {$re['title']} en calendario por defecto {$defaultCalendar->id}");
                        $attachments = null;
                        if (array_key_exists('attachments_meta', $re) && is_array($re['attachments_meta']) && !empty($re['attachments_meta'])) {
                            $attachments = $this->storeRemoteAttachments($re['attachments_meta'], $re['uid']);
                        }
                        $local = new Event([
                            'calendar_id' => $defaultCalendar->id, // Create in Default Calendar
                            'title' => $re['title'],
                            'description' => $re['description'],
                            'starts_at' => $re['starts_at'],
                            'ends_at' => $re['ends_at'],
                            'all_day' => $re['all_day'],
                            'created_by' => $this->userId,
                        ]);
                        if ($attachments !== null) {
                            $local->attachments = $attachments;
                        }
                        $local->caldav_uid = $re['uid'];
                        $local->caldav_etag = $re['etag'] ?? $etag;
                        $local->caldav_href = $href;
                        $local->caldav_last_sync_at = now();
                        $local->save();
                    } else {
                        // Protection against overwrite
                        if ($local->caldav_last_sync_at && $local->updated_at > $local->caldav_last_sync_at) {
                            Log::info("CalDavSyncJob: Omitiendo actualización desde nube para evento {$local->id} (tiene cambios locales pendientes)");
                            continue;
                        }

                        Log::info("CalDavSyncJob: Actualizando evento local {$local->id} en calendario {$local->calendar_id}");
                        
                        $attachments = $local->attachments;
                        if (array_key_exists('attachments_meta', $re) && is_array($re['attachments_meta']) && !empty($re['attachments_meta'])) {
                            $newAttachments = $this->storeRemoteAttachments($re['attachments_meta'], $re['uid']);
                            if ($newAttachments !== null) {
                                $attachments = $newAttachments;
                            }
                        }
                        if (array_key_exists('has_attachments', $re) && !$re['has_attachments']) {
                            $attachments = null;
                        }

                        $local->update([
                            'title' => $re['title'],
                            'description' => $re['description'],
                            'starts_at' => $re['starts_at'],
                            'ends_at' => $re['ends_at'],
                            'all_day' => $re['all_day'],
                            'attachments' => $attachments,
                            'caldav_etag' => $re['etag'] ?? $etag,
                            'caldav_href' => $href,
                            'caldav_last_sync_at' => now(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("CalDavSyncJob: Error guardando evento {$re['uid']}: " . $e->getMessage());
                }
            }
        }

        // Clean processedUids list (unique values)
        $processedUids = array_values(array_unique($processedUids));
        
        // Delete local events absent in cloud (Sync Down Deletions)
        // We check across ALL personal calendars to be safe
        if ($completed) {
            $deletedLocals = Event::join('calendars', 'events.calendar_id', '=', 'calendars.id')
                ->where('calendars.user_id', $this->userId)
                ->where('calendars.is_personal', true)
                ->whereNotNull('events.caldav_uid')
                ->whereNotIn('events.caldav_uid', $processedUids)
                ->select('events.*')
                ->get();

            foreach ($deletedLocals as $localDeleted) {
                Log::info("CalDavSyncJob: Eliminando evento local ausente en CalDAV {$localDeleted->id}", [
                    'uid' => $localDeleted->caldav_uid,
                ]);
                $localDeleted->delete();
            }

            // Sync Up (Push local changes to cloud)
            // Ideally we should sync up from all calendars too?
            // The service->syncUp takes a calendar. 
            // We should iterate all personal calendars to push their changes?
            // Yes, if I created an event in "Entrevistas", I want it pushed.
            $allPersonalCalendars = Calendar::where('user_id', $this->userId)->where('is_personal', true)->get();
            foreach ($allPersonalCalendars as $cal) {
                 $service->syncUp($emailAccount, $cal);
            }

            // Clean Duplicates Step
            // Find UIDs that appear more than once in personal calendars
            $duplicates = Event::join('calendars', 'events.calendar_id', '=', 'calendars.id')
                ->where('calendars.user_id', $this->userId)
                ->where('calendars.is_personal', true)
                ->whereNotNull('events.caldav_uid')
                ->selectRaw('events.caldav_uid, count(*) as count')
                ->groupBy('events.caldav_uid')
                ->having('count', '>', 1)
                ->pluck('caldav_uid');

            if ($duplicates->isNotEmpty()) {
                Log::info("CalDavSyncJob: Limpiando duplicados para " . $duplicates->count() . " eventos.");
                foreach ($duplicates as $dupeUid) {
                    // Keep the one in default calendar, or the oldest one, or the one with most recent update?
                    // Let's keep the one in default calendar if exists, otherwise first one.
                    $events = Event::join('calendars', 'events.calendar_id', '=', 'calendars.id')
                        ->where('calendars.user_id', $this->userId)
                        ->where('calendars.is_personal', true)
                        ->where('events.caldav_uid', $dupeUid)
                        ->select('events.*')
                        ->orderBy('events.calendar_id', 'asc') // Arbitrary order
                        ->get();
                    
                    // Logic: Keep the one in $defaultCalendar if present
                    $keep = $events->first(fn($e) => $e->calendar_id === $defaultCalendar->id) ?? $events->first();
                    
                    foreach ($events as $e) {
                        if ($e->id !== $keep->id) {
                            Log::info("CalDavSyncJob: Borrando duplicado local ID {$e->id} (UID: $dupeUid)");
                            $e->delete();
                        }
                    }
                }
            }

        } else {
            Log::info("CalDavSyncJob: Sincronización incompleta (timeout), saltando eliminación de eventos y syncUp.");
        }

        if ($stopProcessing) {
             Log::warning("CalDavSyncJob: Tiempo límite alcanzado. Re-programando job para continuar.");
             self::dispatch($this->userId);
        }
    }
}
