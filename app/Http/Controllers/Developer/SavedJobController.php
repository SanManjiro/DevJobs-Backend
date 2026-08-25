<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobResource;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavedJobController extends Controller
{
    /**
     * GET /v1/developer/saved-jobs
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $saved = $request->user()
            ->savedJobs()
            ->with(['company.companyProfile', 'skills'])
            ->latest('saved_jobs.created_at')
            ->paginate(15);

        return JobResource::collection($saved);
    }

    /**
     * GET /v1/developer/saved-jobs/ids
     *
     * Juste la liste des identifiants sauvegardés. Le bouton « Save » d'une page
     * publique a besoin de connaître son état initial sans que la ressource
     * publique (JobResource) n'ait à porter un état propre à l'utilisateur.
     */
    public function ids(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->savedJobs()->pluck('job_listings.id'),
        ]);
    }

    /**
     * POST /v1/developer/jobs/{job}/save
     *
     * Idempotent : sauvegarder deux fois ne crée pas de doublon.
     */
    public function store(Request $request, JobListing $job): JsonResponse
    {
        $request->user()->savedJobs()->syncWithoutDetaching([$job->id]);

        return response()->json(['message' => 'Offre sauvegardée.']);
    }

    /**
     * DELETE /v1/developer/jobs/{job}/save
     */
    public function destroy(Request $request, JobListing $job): JsonResponse
    {
        $request->user()->savedJobs()->detach($job->id);

        return response()->json(['message' => 'Offre retirée des favoris.']);
    }
}
