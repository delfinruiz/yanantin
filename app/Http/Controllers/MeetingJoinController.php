<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Meeting;
use App\Models\MeetingParticipant;

class MeetingJoinController
{
    public function __invoke(Request $request, Meeting $meeting)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $participant = MeetingParticipant::firstOrCreate(
            [
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
            ],
            [
                'status' => 'accepted',
            ]
        );

        $participant->status = 'accepted';
        if ($participant->isDirty('status')) {
            $participant->save();
        }
        $participant->joined_at = now();
        $participant->save();

        return redirect()->away($meeting->join_url ?: url('/'));
    }
}
