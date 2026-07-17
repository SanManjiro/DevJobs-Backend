<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationController extends Controller
{
    /**
     * GET /v1/company/jobs/{job}/applications
     *
     * Les candidatures reçues sur une offre — réservées à l'entreprise
     * propriétaire de l'offre.
     */
    public function index(Request $request, JobListing $job): AnonymousResourceCollection
    {
        abort_if($job->company_id !== $request->user()->id, 403, 'Action non autorisée.');

        $applications = $job->applications()
            ->with('developer')
            ->latest()
            ->paginate(20);

        return ApplicationResource::collection($applications);
    }

    /**
     * PATCH /v1/company/applications/{application}/status
     *
     * L'entreprise fait avancer une candidature. Elle ne peut pas la remettre
     * en « pending » : ce statut n'appartient qu'au moment de la soumission.
     */
    public function updateStatus(Request $request, Application $application): ApplicationResource
    {
        abort_if(
            $application->job->company_id !== $request->user()->id,
            403,
            'Action non autorisée.',
        );

        $data = $request->validate([
            'status' => ['required', 'in:viewed,accepted,rejected'],
        ]);

        $application->update(['status' => $data['status']]);

        return new ApplicationResource($application->load('developer'));
    }
}
