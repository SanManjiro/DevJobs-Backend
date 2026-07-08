<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobListingRequest;
use App\Http\Requests\UpdateJobListingRequest;
use App\Models\JobListing;
use App\Services\JobListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobListingController extends Controller
{
    public function __construct(private JobListingService $jobService) {}

    /**
     * Liste les offres publiées avec filtres et pagination.
     *
     * GET /api/v1/jobs
     * Filtres : type, remote, experience_level, location, search, skill
     */
    public function index(Request $request): JsonResponse
    {
        $jobs = $this->jobService->list($request);

        return response()->json($jobs);
    }

    /**
     * Crée une nouvelle offre d'emploi (entreprise uniquement).
     *
     * POST /api/v1/jobs
     */
    public function store(StoreJobListingRequest $request): JsonResponse
    {
        $job = $this->jobService->create(
            company: $request->user(),
            data: $request->validated(),
        );

        return response()->json($job, 201);
    }

    /**
     * Affiche une offre spécifique avec ses compétences et le profil entreprise.
     *
     * GET /api/v1/jobs/{job}
     */
    public function show(JobListing $job): JsonResponse
    {
        $job->load(['company.companyProfile', 'skills']);

        return response()->json($job);
    }

    /**
     * Met à jour une offre (propriétaire uniquement, vérifié dans UpdateJobListingRequest).
     *
     * PUT/PATCH /api/v1/jobs/{job}
     */
    public function update(UpdateJobListingRequest $request, JobListing $job): JsonResponse
    {
        $updated = $this->jobService->update($job, $request->validated());

        return response()->json($updated);
    }

    /**
     * Supprime une offre (propriétaire uniquement).
     *
     * DELETE /api/v1/jobs/{job}
     */
    public function destroy(Request $request, JobListing $job): JsonResponse
    {
        // Vérifie que l'utilisateur est bien le propriétaire
        if ($job->company_id !== $request->user()->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $this->jobService->delete($job);

        return response()->json(['message' => 'Offre supprimée avec succès.']);
    }
}
