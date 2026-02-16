<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurveyApiController extends Controller
{
    public function pendingCount(Request $request)
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['count' => 0]);
        }

        $count = Survey::whereHas('users', fn ($q) => $q->where('users.id', $userId))
            ->where(function ($q) {
                $q->whereNull('deadline')
                  ->orWhere('deadline', '>=', now());
            })
            ->whereHas('questions', function ($q) use ($userId) {
                $q->where('required', true)
                  ->whereDoesntHave('responses', fn ($r) => $r->where('user_id', $userId));
            })->count();

        return response()->json(['count' => $count]);
    }
}
