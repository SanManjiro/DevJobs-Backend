<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobResource;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JobController extends Controller
{
    /**
     * GET /v1/admin/jobs
     *
     * Toutes les offres, tous statuts, avec l'entreprise et le nombre de
     * candidatures — la modération voit ce que le public ne voit pas.
     */
    public function index(): AnonymousResourceCollection
    {
        $jobs = JobListing::with('company.companyProfile')
            ->withCount('applications')
            ->latest()
            ->paginate(20);

        return JobResource::collection($jobs);
    }

    /**
     * DELETE /v1/admin/jobs/{job}
     */
    public function destroy(JobListing $job): JsonResponse
    {
        $job->delete();

        return response()->json(['message' => 'Offre supprimée.']);
    }
}
