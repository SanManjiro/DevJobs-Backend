<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobResource;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Lecture des offres de l'entreprise connectée, tous statuts confondus
 * (brouillons compris). L'écriture (create / update / delete) reste sur
 * /v1/jobs — décision assumée et documentée.
 */
class JobController extends Controller
{
    /**
     * GET /v1/company/jobs
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $jobs = $request->user()
            ->jobListings()
            ->with('skills')
            ->withCount('applications')
            ->latest()
            ->paginate(15);

        return JobResource::collection($jobs);
    }

    /**
     * GET /v1/company/jobs/{job}
     *
     * Pour préremplir le formulaire d'édition : contrairement à la route
     * publique, elle renvoie aussi les brouillons — mais du propriétaire seul.
     */
    public function show(Request $request, JobListing $job): JobResource
    {
        abort_if($job->company_id !== $request->user()->id, 403, 'Action non autorisée.');

        $job->load('skills')->loadCount('applications');

        return new JobResource($job);
    }
}
