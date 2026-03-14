<?php

namespace App\Http\Controllers;

use App\Models\Response;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurveyRespondController extends Controller
{
    public function show(Survey $survey)
    {
        \Illuminate\Support\Facades\Log::info('SurveyRespondController.show hit', [
            'survey_id' => $survey->id,
            'user_id' => Auth::id(),
            'path' => request()->path(),
        ]);
        $userId = Auth::id();
        $isAssigned = $survey->users()->where('users.id', $userId)->exists();
        \Illuminate\Support\Facades\Log::info('SurveyRespondController.assignment', [
            'survey_id' => $survey->id,
            'user_id' => $userId,
            'is_assigned' => $isAssigned,
        ]);
        abort_unless($isAssigned, 403);
        if (! $survey->active) {
            return view('surveys.thanks', [
                'survey' => $survey,
                'status' => 'inactive',
                'message' => 'Esta encuesta aún no está activa',
            ]);
        }

        $questions = $survey->questions()->orderBy('order')->get();
        $state = [];
        foreach ($questions as $q) {
            $existing = Response::where('question_id', $q->id)->where('user_id', $userId)->first();
            $value = $existing ? $existing->value : null;
            $state['q_'.$q->id] = $value === 'Sin Respuesta' ? null : $value;
        }

        return view('surveys.respond', [
            'survey' => $survey,
            'questions' => $questions,
            'state' => $state,
        ]);
    }

    public function submit(Request $request, Survey $survey)
    {
        \Illuminate\Support\Facades\Log::info('SurveyRespondController.submit hit', [
            'survey_id' => $survey->id,
            'user_id' => Auth::id(),
        ]);
        $userId = Auth::id();
        $isAssigned = $survey->users()->where('users.id', $userId)->exists();
        \Illuminate\Support\Facades\Log::info('SurveyRespondController.submit assignment', [
            'survey_id' => $survey->id,
            'user_id' => $userId,
            'is_assigned' => $isAssigned,
        ]);
        abort_unless($isAssigned, 403);
        if (! $survey->active) {
            return back()->with('error', 'Esta encuesta aún no está activa');
        }

        $questions = $survey->questions()->orderBy('order')->get();
        $rules = [];
        $messages = [];

        foreach ($questions as $q) {
            if (! $q->required) {
                continue;
            }

            $key = 'q_'.$q->id;
            if ($q->type === 'multi') {
                $rules[$key] = ['required', 'array', 'min:1'];
                $messages["{$key}.required"] = 'Esta pregunta es obligatoria.';
                $messages["{$key}.min"] = 'Esta pregunta es obligatoria.';
            } else {
                $rules[$key] = ['required'];
                $messages["{$key}.required"] = 'Esta pregunta es obligatoria.';
            }
        }

        $request->validate($rules, $messages);

        foreach ($questions as $q) {
            $key = 'q_'.$q->id;
            $val = $request->input($key);

            if (is_string($val) && trim($val) === '') {
                $val = null;
            }

            if (is_array($val) && empty($val)) {
                $val = null;
            }

            if (! $q->required && $val === null) {
                $val = 'Sin Respuesta';
            }

            if ($val !== null) {
                Response::updateOrCreate(
                    ['question_id' => $q->id, 'user_id' => $userId],
                    ['value' => is_array($val) ? json_encode($val) : (string) $val]
                );
            }
        }

        return view('surveys.thanks', [
            'survey' => $survey,
            'status' => 'submitted',
            'message' => 'Gracias por su respuesta',
        ]);
    }
}
