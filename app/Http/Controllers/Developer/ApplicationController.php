<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationController extends Controller
{
    /**
     * GET /v1/developer/applications
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $applications = $request->user()
            ->applications()
            ->with('job.company.companyProfile')
            ->latest()
            ->paginate(15);

        return ApplicationResource::collection($applications);
    }

    /**
     * POST /v1/developer/jobs/{job}/apply
     *
     * Les règles métier renvoient 422 (l'offre existe mais n'accepte pas la
     * candidature), pas 404 : le développeur voit un message, pas une page
     * introuvable.
     */
    public function store(Request $request, JobListing $job): JsonResponse
    {
        if ($job->status !== 'published') {
            return response()->json(['message' => "Cette offre n'est pas disponible."], 422);
        }

        if ($job->expires_at && $job->expires_at->isPast()) {
            return response()->json(['message' => 'Cette offre a expiré.'], 422);
        }

        $alreadyApplied = Application::where('developer_id', $request->user()->id)
            ->where('job_id', $job->id)
            ->exists();

        if ($alreadyApplied) {
            return response()->json(['message' => 'Vous avez déjà postulé à cette offre.'], 422);
        }

        $data = $request->validate([
            'coverLetter' => 'nullable|string|max:3000',
        ]);

        $application = Application::create([
            'developer_id' => $request->user()->id,
            'job_id'       => $job->id,
            'cover_letter' => $data['coverLetter'] ?? null,
            // Explicite : le défaut DB ('pending') n'hydrate pas le modèle en
            // mémoire, donc la ressource sérialiserait un statut null sans ça.
            'status'       => 'pending',
        ]);

        $application->load('job.company.companyProfile');

        return (new ApplicationResource($application))->response()->setStatusCode(201);
    }

    /**
     * DELETE /v1/developer/applications/{application}
     *
     * Un retrait n'est possible que tant que l'entreprise n'a pas traité la
     * candidature (statut « pending »).
     */
    public function destroy(Request $request, Application $application): JsonResponse
    {
        if ($application->developer_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json(
                ['message' => 'Impossible de retirer une candidature déjà traitée.'],
                422,
            );
        }

        $application->delete();

        return response()->json(['message' => 'Candidature retirée.']);
    }
}
