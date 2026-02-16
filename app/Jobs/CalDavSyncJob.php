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
        $emailAccount = EmailAccount::where('user_id', $this->userId)->first();
        if (!$emailAccount || empty($emailAccount->encrypted_password)) {
            return;
        }

        $service = new CalDavService();
        $calendars = Calendar::where('user_id', $this->userId)->where('is_personal', true)->get();

        foreach ($calendars as $calendar) {
            Log::info("CalDavSyncJob: Sincronizando calendario {$calendar->id} para usuario {$this->userId}");
            $remoteEvents = $service->syncDown($emailAccount, $calendar, $calendar->caldav_sync_token ?? null);
            Log::info("CalDavSyncJob: Encontrados " . count($remoteEvents) . " eventos remotos");

            foreach ($remoteEvents as $re) {
                // Ajustar zona horaria para eventos que no son de todo el día
                // CalDavParser devuelve Carbon en UTC (si el ICS tiene Z), pero necesitamos guardar en la BD
                // con la hora local de la aplicación para que Filament y otros componentes lo muestren bien.
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
                    $local = Event::where('calendar_id', $calendar->id)
                        ->where('caldav_uid', $re['uid'])->first();
                    if (!$local) {
                        Log::info("CalDavSyncJob: Creando nuevo evento local {$re['title']}");
                        $attachments = null;
                        if (array_key_exists('attachments_meta', $re) && is_array($re['attachments_meta']) && !empty($re['attachments_meta'])) {
                            $attachments = $this->storeRemoteAttachments($re['attachments_meta'], $re['uid']);
                        }
                        $local = new Event([
                            'calendar_id' => $calendar->id,
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
                        $local->caldav_etag = $re['etag'] ?? null;
                        $local->caldav_last_sync_at = now();
                        $local->save();
                        Log::info("CalDavSyncJob: Evento creado ID {$local->id}");
                    } elseif ($local) {
                        // Protección contra sobreescritura: Si el evento local tiene cambios pendientes de subir,
                        // no lo actualizamos desde la nube (la nube podría tener datos viejos o conflicto).
                        // Dejamos que syncUp() se encargue de subir los cambios locales después.
                        if ($local->caldav_last_sync_at && $local->updated_at > $local->caldav_last_sync_at) {
                            Log::info("CalDavSyncJob: Omitiendo actualización desde nube para evento {$local->id} (tiene cambios locales pendientes)");
                            continue;
                        }

                        Log::info("CalDavSyncJob: Actualizando evento local {$local->id}");
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
                        ]);
                        $local->caldav_etag = $re['etag'] ?? $local->caldav_etag;
                        $local->caldav_last_sync_at = now();
                        $local->save();
                    }
                } catch (\Exception $e) {
                    Log::error("CalDavSyncJob: Error guardando evento {$re['uid']}: " . $e->getMessage());
                }
            }

            $remoteUids = collect($remoteEvents)->pluck('uid')->filter()->values()->all();
            
            // Eliminamos eventos locales que ya no existen en la nube.
            // Nota: Si $remoteUids está vacío (se borró todo en la nube), whereNotIn con array vacío
            // no aplica filtro, por lo que devolverá todos los eventos con caldav_uid, lo cual es correcto.
            $deletedLocals = Event::where('calendar_id', $calendar->id)
                ->whereNotNull('caldav_uid')
                ->whereNotIn('caldav_uid', $remoteUids)
                ->get();

            foreach ($deletedLocals as $localDeleted) {
                Log::info("CalDavSyncJob: Eliminando evento local ausente en CalDAV {$localDeleted->id}", [
                    'uid' => $localDeleted->caldav_uid,
                ]);
                $localDeleted->delete();
            }

            $service->syncUp($emailAccount, $calendar);
        }
    }
}
