<?php

namespace App\Observers;

use App\Models\JobInterview;
use App\Models\Event;
use App\Models\Calendar;
use App\Models\EmailAccount;
use App\Services\CalDav\CalDavService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class JobInterviewObserver
{
    private function getPrimaryPersonalCalendarId(int $userId): int
    {
        $calendar = Calendar::where('user_id', $userId)
            ->where('is_personal', true)
            ->orderByRaw("name = 'Entrevistas' asc")
            ->orderByRaw('created_by = ? desc', [$userId])
            ->orderBy('id')
            ->first();

        if ($calendar) {
            return $calendar->id;
        }

        $calendar = Calendar::create([
            'user_id' => $userId,
            'name' => 'Calendario Personal',
            'is_public' => false,
            'is_personal' => true,
            'created_by' => $userId,
        ]);

        return $calendar->id;
    }

    private function hasCalendarConflict(int $userId, Carbon $startsAt, Carbon $endsAt, ?int $ignoreEventId = null): bool
    {
        return Event::query()
            ->when($ignoreEventId, fn ($query) => $query->where('id', '!=', $ignoreEventId))
            ->where(function ($query) use ($userId) {
                $query
                    ->whereHas('calendar', function ($q) use ($userId) {
                        $q->where('user_id', $userId)->where('is_personal', true);
                    })
                    ->orWhereHas('sharedWith', function ($q) use ($userId) {
                        $q->where('users.id', $userId);
                    });
            })
            ->where(function ($query) use ($startsAt, $endsAt) {
                $query
                    ->where(function ($q) use ($startsAt, $endsAt) {
                        $q->where('all_day', false)
                            ->where('starts_at', '<', $endsAt)
                            ->whereRaw('COALESCE(ends_at, starts_at) > ?', [$startsAt]);
                    })
                    ->orWhere(function ($q) use ($startsAt, $endsAt) {
                        $startDate = $startsAt->toDateString();
                        $endDate = $endsAt->toDateString();

                        $q->where('all_day', true)
                            ->whereDate('starts_at', '<=', $endDate)
                            ->where(function ($q2) use ($startDate) {
                                $q2->whereDate('ends_at', '>=', $startDate)
                                    ->orWhere(function ($q3) use ($startDate) {
                                        $q3->whereNull('ends_at')->whereDate('starts_at', '>=', $startDate);
                                    });
                            });
                    });
            })
            ->exists();
    }

    private function assertNoConflict(int $userId, Carbon $scheduledAt, ?int $ignoreEventId = null): void
    {
        $startsAt = $scheduledAt->copy();
        $endsAt = $scheduledAt->copy()->addHour();

        if ($this->hasCalendarConflict($userId, $startsAt, $endsAt, $ignoreEventId)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'El entrevistador ya tiene un evento agendado en ese horario.',
            ]);
        }
    }

    public function creating(JobInterview $jobInterview): void
    {
        if (!$jobInterview->interviewer_id || !$jobInterview->scheduled_at) {
            return;
        }

        if (($jobInterview->status ?? 'scheduled') !== 'scheduled') {
            return;
        }

        $this->assertNoConflict(
            $jobInterview->interviewer_id,
            Carbon::parse($jobInterview->scheduled_at),
        );
    }

    public function updating(JobInterview $jobInterview): void
    {
        if (!($jobInterview->isDirty('scheduled_at') || $jobInterview->isDirty('interviewer_id'))) {
            return;
        }

        $interviewerId = $jobInterview->interviewer_id;
        $scheduledAt = $jobInterview->scheduled_at;

        if (!$interviewerId || !$scheduledAt) {
            return;
        }

        if (($jobInterview->status ?? 'scheduled') !== 'scheduled') {
            return;
        }

        $this->assertNoConflict(
            $interviewerId,
            Carbon::parse($scheduledAt),
            $jobInterview->event_id,
        );
    }

    /**
     * Handle the JobInterview "created" event.
     */
    public function created(JobInterview $jobInterview): void
    {
        $jobApplication = $jobInterview->jobApplication;
        if ($jobApplication && ! in_array($jobApplication->status, ['hired', 'rejected', 'cancelled'], true) && $jobApplication->status !== 'interview') {
            $jobApplication->forceFill(['status' => 'interview'])->saveQuietly();
        }

        $interviewer = $jobInterview->interviewer;
        
        if (!$interviewer) {
            return;
        }

        $calendarId = $this->getPrimaryPersonalCalendarId($interviewer->id);

        $event = Event::create([
            'calendar_id' => $calendarId,
            'title' => 'Entrevista: ' . ($jobInterview->jobApplication->applicant_name ?? 'Candidato'),
            'description' => 'Entrevista para oferta: ' . ($jobInterview->jobApplication->jobOffer->title ?? 'N/A'),
            'starts_at' => $jobInterview->scheduled_at,
            'ends_at' => Carbon::parse($jobInterview->scheduled_at)->addHour(),
            'all_day' => false,
            'created_by' => $interviewer->id,
        ]);

        $jobInterview->event_id = $event->id;
        $jobInterview->saveQuietly();

        try {
            $emailAccount = EmailAccount::where('user_id', $interviewer->id)->first();
            $calendar = Calendar::find($calendarId);

            if ($emailAccount && $calendar && $calendar->is_personal && $calendar->user_id === $interviewer->id) {
                $service = app(CalDavService::class);
                $result = $service->createEvent($emailAccount, $calendar, $event);
                $event->caldav_uid = $result['uid'] ?? $event->caldav_uid;
                $event->caldav_etag = $result['etag'] ?? null;
                $event->caldav_last_sync_at = now();
                $event->save();
            }
        } catch (\Throwable $e) {
            Log::error('CalDAV Create Error (JobInterview): ' . $e->getMessage(), [
                'job_interview_id' => $jobInterview->id,
                'event_id' => $event->id,
            ]);
        }
    }

    /**
     * Handle the JobInterview "updated" event.
     */
    public function updated(JobInterview $jobInterview): void
    {
        if (!$jobInterview->event_id) {
            return;
        }

        $event = Event::find($jobInterview->event_id);
        if (!$event) {
            return;
        }

        $interviewer = $jobInterview->interviewer;

        if ($jobInterview->wasChanged('interviewer_id')) {
            if ($interviewer) {
                $event->calendar_id = $this->getPrimaryPersonalCalendarId($interviewer->id);
                $event->created_by = $interviewer->id;
                $event->save();
            }
        }

        if ($jobInterview->wasChanged('scheduled_at')) {
            $event->update([
                'starts_at' => $jobInterview->scheduled_at,
                'ends_at' => Carbon::parse($jobInterview->scheduled_at)->addHour(),
            ]);
        }

        if ($interviewer) {
            try {
                $emailAccount = EmailAccount::where('user_id', $interviewer->id)->first();
                $calendar = Calendar::find($event->calendar_id);

                if ($emailAccount && $calendar && $calendar->is_personal && $calendar->user_id === $interviewer->id) {
                    $service = app(CalDavService::class);

                    if ($event->caldav_uid) {
                        $etag = $service->updateEvent($emailAccount, $calendar, $event);
                        if ($etag) {
                            $event->caldav_etag = $etag;
                        }
                    } else {
                        $result = $service->createEvent($emailAccount, $calendar, $event);
                        $event->caldav_uid = $result['uid'] ?? $event->caldav_uid;
                        $event->caldav_etag = $result['etag'] ?? null;
                    }

                    $event->caldav_last_sync_at = now();
                    $event->save();
                }
            } catch (\Throwable $e) {
                Log::error('CalDAV Update Error (JobInterview): ' . $e->getMessage(), [
                    'job_interview_id' => $jobInterview->id,
                    'event_id' => $event->id,
                ]);
            }
        }
    }

    /**
     * Handle the JobInterview "deleted" event.
     */
    public function deleted(JobInterview $jobInterview): void
    {
        if (!$jobInterview->event_id) {
            return;
        }

        $event = Event::find($jobInterview->event_id);
        if (!$event) {
            return;
        }

        try {
            $calendar = Calendar::find($event->calendar_id);
            if ($calendar && $calendar->is_personal && $calendar->user_id) {
                $emailAccount = EmailAccount::where('user_id', $calendar->user_id)->first();
                if ($emailAccount && $event->caldav_uid) {
                    $service = app(CalDavService::class);
                    $service->deleteEvent($emailAccount, $event);
                }
            }
        } catch (\Throwable $e) {
            Log::error('CalDAV Delete Error (JobInterview): ' . $e->getMessage(), [
                'job_interview_id' => $jobInterview->id,
                'event_id' => $event->id,
            ]);
        }

        $event->delete();
    }
}
