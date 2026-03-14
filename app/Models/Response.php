<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\JobInterview;
use Illuminate\Support\Facades\Schema;

class Response extends Model
{
    protected static ?bool $hasJobInterviewIdColumn = null;

    protected $fillable = [
        'question_id',
        'job_interview_id',
        'user_id',
        'guest_email',
        'guest_name',
        'value',
    ];

    protected $casts = [
        'job_interview_id' => 'integer',
    ];

    public static function hasJobInterviewIdColumn(): bool
    {
        if (static::$hasJobInterviewIdColumn === null) {
            static::$hasJobInterviewIdColumn = Schema::hasColumn((new static())->getTable(), 'job_interview_id');
        }

        return static::$hasJobInterviewIdColumn;
    }

    public function scopeWithoutInterview(Builder $query): Builder
    {
        if (! static::hasJobInterviewIdColumn()) {
            return $query;
        }

        return $query->whereNull('job_interview_id');
    }

    public function scopeForInterview(Builder $query, int $jobInterviewId): Builder
    {
        if (! static::hasJobInterviewIdColumn()) {
            return $query;
        }

        return $query->where('job_interview_id', $jobInterviewId);
    }

    public static function backfillInterviewResponses(JobInterview $interview, array $questionIds): void
    {
        if (! static::hasJobInterviewIdColumn()) {
            return;
        }

        $interviewId = (int) ($interview->id ?? 0);
        $userId = (int) ($interview->interviewer_id ?? 0);

        if ($interviewId <= 0 || $userId <= 0 || empty($questionIds)) {
            return;
        }

        $existingQuestionIds = static::query()
            ->forInterview($interviewId)
            ->whereIn('question_id', $questionIds)
            ->pluck('question_id')
            ->all();

        $missingQuestionIds = array_values(array_diff($questionIds, $existingQuestionIds));

        if (empty($missingQuestionIds)) {
            return;
        }

        $legacy = static::query()
            ->withoutInterview()
            ->where('user_id', $userId)
            ->whereIn('question_id', $missingQuestionIds)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get(['question_id', 'value', 'user_id']);

        $byQuestion = $legacy->groupBy('question_id')->map->first();

        foreach ($missingQuestionIds as $questionId) {
            $row = $byQuestion->get($questionId);

            if (! $row) {
                continue;
            }

            $value = $row->value;
            if ($value === null || (is_string($value) && trim($value) === '') || $value === 'Sin Respuesta') {
                continue;
            }

            static::query()->create([
                'question_id' => (int) $questionId,
                'job_interview_id' => $interviewId,
                'user_id' => (int) $row->user_id,
                'value' => $value,
            ]);
        }
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function jobInterview(): BelongsTo
    {
        return $this->belongsTo(JobInterview::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }
}
