<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use Illuminate\Http\Request;

class PublicDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $offers = JobOffer::query()->active()
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return view('pages.public.dashboard', [
            'user' => $user,
            'offers' => $offers,
            'applications' => [], // placeholder para futuro
        ]);
    }
}

