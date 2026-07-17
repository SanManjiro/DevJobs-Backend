<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeveloperProfileResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /v1/developer/profile
     */
    public function show(Request $request): DeveloperProfileResource
    {
        $profile = $request->user()->developerProfile()->with('skills')->firstOrFail();

        return new DeveloperProfileResource($profile);
    }

    /**
     * PUT /v1/developer/profile
     *
     * Le contrat est camelCase des deux côtés ; on valide les clés telles que
     * le formulaire les poste, puis on les traduit vers les colonnes snake_case.
     */
    public function update(Request $request): DeveloperProfileResource
    {
        $data = $request->validate([
            'bio'             => 'nullable|string|max:2000',
            'location'        => 'nullable|string|max:100',
            'githubUrl'       => 'nullable|url|max:255',
            'portfolioUrl'    => 'nullable|url|max:255',
            'yearsExperience' => 'nullable|integer|min:0|max:50',
        ]);

        $profile = $request->user()->developerProfile;
        $profile->update([
            'bio'              => $data['bio'] ?? null,
            'location'         => $data['location'] ?? null,
            'github_url'       => $data['githubUrl'] ?? null,
            'portfolio_url'    => $data['portfolioUrl'] ?? null,
            'years_experience' => $data['yearsExperience'] ?? 0,
        ]);

        return new DeveloperProfileResource($profile->load('skills'));
    }

    /**
     * POST /v1/developer/profile/skills
     *
     * Ajoute sans détacher : reposter une compétence déjà présente met
     * simplement à jour son niveau, sans effacer les autres.
     */
    public function addSkills(Request $request): DeveloperProfileResource
    {
        $data = $request->validate([
            'skills'         => 'required|array|min:1',
            'skills.*.id'    => 'required|integer|exists:skills,id',
            'skills.*.level' => 'nullable|in:junior,intermediaire,senior',
        ]);

        $profile = $request->user()->developerProfile;

        $sync = collect($data['skills'])->mapWithKeys(fn ($s) => [
            $s['id'] => ['level' => $s['level'] ?? 'junior'],
        ])->all();

        $profile->skills()->syncWithoutDetaching($sync);

        return new DeveloperProfileResource($profile->load('skills'));
    }

    /**
     * DELETE /v1/developer/profile/skills/{skill}
     */
    public function removeSkill(Request $request, int $skill): DeveloperProfileResource
    {
        $profile = $request->user()->developerProfile;
        $profile->skills()->detach($skill);

        return new DeveloperProfileResource($profile->load('skills'));
    }
}
