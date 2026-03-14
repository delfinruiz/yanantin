<?php

namespace App\Http\Controllers;

use App\Exports\SurveyResponsesExport;
use App\Exports\FormSubmissionsExport;
use App\Models\JobInterview;
use App\Models\Response;
use App\Models\Survey;
use Maatwebsite\Excel\Facades\Excel;

class SurveyExportController extends Controller
{
    public function exportResponses(Survey $survey)
    {
        $filename = 'Respuestas_' . str_replace([' ', '/', '\\'], '_', $survey->title) . '.xlsx';
        return Excel::download(new SurveyResponsesExport($survey), $filename);
    }

    public function exportInterviewResponses(JobInterview $interview)
    {
        abort_unless(($interview->status ?? null) === 'completed', 404);

        $survey = $interview->survey()->with('questions')->first();
        abort_unless($survey, 404);

        $questions = $survey->questions;
        $qIds = $questions->pluck('id')->all();

        $responsesByQid = Response::query()
            ->forInterview($interview->id)
            ->whereIn('question_id', $qIds)
            ->get(['question_id', 'value'])
            ->keyBy('question_id');

        if ($responsesByQid->isEmpty()) {
            Response::backfillInterviewResponses($interview, $qIds);

            $responsesByQid = Response::query()
                ->forInterview($interview->id)
                ->whereIn('question_id', $qIds)
                ->get(['question_id', 'value'])
                ->keyBy('question_id');
        }

        $headers = [
            'Dimensión',
            'Pregunta',
            'Respuesta',
        ];

        $rows = [];
        foreach ($questions as $q) {
            $raw = $responsesByQid->get($q->id)?->value;
            $value = $raw;

            if (is_string($value) && $value !== '' && str_starts_with(trim($value), '[')) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = implode(', ', $decoded);
                }
            }

            if ($value === null || (is_string($value) && trim($value) === '')) {
                $value = 'Sin Respuesta';
            }

            $rows[] = [
                (string) ($q->item ?: 'General'),
                (string) $q->content,
                (string) $value,
            ];
        }

        $candidate = $interview->jobApplication?->applicant_name ?? 'candidato';
        $filename = 'entrevista_' . $interview->id . '_' . str($candidate)->slug('_') . '.xlsx';

        return Excel::download(new FormSubmissionsExport($rows, $headers), $filename);
    }
}
