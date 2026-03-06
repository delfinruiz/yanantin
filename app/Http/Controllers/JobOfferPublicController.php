<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class JobOfferPublicController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('job_offers')) {
            return view('pages.portada.careers', [
                'offers' => collect()->paginate(10),
                'filters' => ['q' => null, 'location' => null, 'contract_type' => null],
                'locations' => collect(),
                'contracts' => collect(),
            ]);
        }

        $q = trim((string) $request->get('q'));
        $location = $request->get('location');
        $contract = $request->get('contract_type');

        $query = JobOffer::query()->active();

        if ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('requirements', 'like', "%{$q}%");
            });
        }
        if ($location) {
            $query->where('location', 'like', "%{$location}%");
        }
        if ($contract) {
            $query->where('contract_type', $contract);
        }

        $offers = $query->with('jobOfferRequirements')->orderByDesc('published_at')->paginate(9)->withQueryString();

        $locations = JobOffer::query()->active()
            ->select('location')->whereNotNull('location')->distinct()->orderBy('location')->pluck('location');
        $contracts = JobOffer::query()->active()
            ->select('contract_type')->whereNotNull('contract_type')->distinct()->orderBy('contract_type')->pluck('contract_type');

        return view('pages.portada.careers', [
            'offers' => $offers,
            'filters' => [
                'q' => $q,
                'location' => $location,
                'contract_type' => $contract,
            ],
            'locations' => $locations,
            'contracts' => $contracts,
        ]);
    }

    public function show(JobOffer $jobOffer)
    {
        abort_unless($jobOffer->is_active, 404);
        return response()->json([
            'id' => $jobOffer->id,
            'title' => $jobOffer->title,
            'description' => $jobOffer->description,
            'requirements' => $jobOffer->requirements,
            'benefits' => $jobOffer->benefits,
            'location' => $jobOffer->location,
            'contract_type' => $jobOffer->contract_type,
            'salary' => $jobOffer->salary,
            'deadline' => optional($jobOffer->deadline)->format('Y-m-d'),
            'published_at' => optional($jobOffer->published_at)->toDateTimeString(),
        ]);
    }
}
