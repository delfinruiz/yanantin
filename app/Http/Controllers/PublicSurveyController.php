<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\User;
use App\Models\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PublicSurveyController extends Controller
{
    protected function findSurveyByToken(string $token): ?Survey
    {
        return Survey::where('public_enabled', true)->where('public_token', $token)->first();
    }

    public function landing(string $token)
    {
        $survey = $this->findSurveyByToken($token);
        if (! $survey) {
            abort(404);
        }
        $link = route('surveys.public.landing', ['token' => $token]);
        $settings = app(\App\Services\SettingService::class)->getSettings();
        $logo = $settings?->logo_light ? Storage::url($settings->logo_light) : asset('/asset/images/logo-light.png');
        $favicon = $settings?->favicon ? Storage::url($settings->favicon) : asset('/asset/images/favicon.ico');
        $company = $settings?->company_name ?? config('app.name', 'Finanzas Personales');
        if (! $survey->active) {
            return view('surveys.public-inactive', compact('survey', 'logo', 'favicon', 'company'));
        }
        return view('surveys.public-landing', compact('survey', 'link', 'token', 'logo', 'favicon', 'company'));
    }

    public function start(Request $request, string $token)
    {
        $survey = $this->findSurveyByToken($token);
        if (! $survey) {
            abort(404);
        }
        if (! $survey->active) {
            return redirect()->route('surveys.public.landing', ['token' => $token]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        // Store guest info in session instead of creating a user
        session()->put("survey_guest_{$token}", [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return redirect()->route('surveys.public.respond', ['token' => $token]);
    }

    public function respond(string $token)
    {
        $survey = $this->findSurveyByToken($token);
        if (! $survey) {
            abort(404);
        }
        if (! $survey->active) {
            return redirect()->route('surveys.public.landing', ['token' => $token]);
        }

        // Check for guest session or authenticated user
        $guest = session("survey_guest_{$token}");
        $user = Auth::user();

        if (!$guest && !$user) {
            return redirect()->route('surveys.public.landing', ['token' => $token]);
        }

        $questions = $survey->questions()->orderBy('order')->get(['id','content','type','required','options','item']);
        $settings = app(\App\Services\SettingService::class)->getSettings();
        $logo = $settings?->logo_light ? Storage::url($settings->logo_light) : asset('/asset/images/logo-light.png');
        $company = $settings?->company_name ?? config('app.name', 'Finanzas Personales');
        $groups = $questions->groupBy(function ($q) {
            return $q->item ?: 'Sección';
        });
        return view('surveys.public-respond', compact('survey', 'questions', 'groups', 'token', 'logo', 'company'));
    }

    public function submit(Request $request, string $token)
    {
        $survey = $this->findSurveyByToken($token);
        if (! $survey) {
            abort(404);
        }
        if (! $survey->active) {
            return redirect()->route('surveys.public.landing', ['token' => $token]);
        }

        $guest = session("survey_guest_{$token}");
        $user = Auth::user();

        if (!$guest && !$user) {
            abort(403);
        }

        $questions = $survey->questions()->orderBy('order')->get(['id','type','required']);
        $rules = [];
        foreach ($questions as $q) {
            $key = 'q_' . $q->id;
            if ($q->required) {
                $rules[$key] = ['required'];
            } else {
                $rules[$key] = ['nullable'];
            }
        }
        $validated = $request->validate($rules);
        
        foreach ($questions as $q) {
            $val = $validated['q_' . $q->id] ?? null;
            if (is_array($val)) {
                $val = json_encode($val);
            }
            
            Response::updateOrCreate(
                [
                    'question_id' => $q->id,
                    'user_id' => $user ? $user->id : null,
                    'guest_email' => $user ? null : $guest['email'],
                ],
                [
                    'value' => $val,
                    'guest_name' => $user ? null : $guest['name'],
                ]
            );
        }
        
        // Optionally clear session or keep it to prevent re-submission loop? 
        // Better to keep it so they can see "Thanks" page or if they navigate back.
        // But maybe clear it on thanks page? No, stateless is better for now.
        
        return redirect()->route('surveys.public.thanks', ['token' => $token]);
    }

    public function thanks(string $token)
    {
        $survey = $this->findSurveyByToken($token);
        if (! $survey) {
            abort(404);
        }
        $settings = app(\App\Services\SettingService::class)->getSettings();
        $logo = $settings?->logo_light ? Storage::url($settings->logo_light) : asset('/asset/images/logo-light.png');
        $company = $settings?->company_name ?? config('app.name', 'Finanzas Personales');
        return view('surveys.public-thanks', compact('survey', 'logo', 'company'));
    }
}
