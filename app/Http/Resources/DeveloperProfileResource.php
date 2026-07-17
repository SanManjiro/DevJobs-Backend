<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Le profil du développeur connecté. camelCase dans les deux sens : le
 * formulaire poste `githubUrl`, la règle valide `githubUrl`, donc les clés du
 * bag 422 sont déjà les `name` des champs. Les compétences portent leur niveau
 * (pivot developer_skill), via SkillResource.
 */
class DeveloperProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'bio'             => $this->bio,
            'location'        => $this->location,
            'githubUrl'       => $this->github_url,
            'portfolioUrl'    => $this->portfolio_url,
            'yearsExperience' => (int) $this->years_experience,
            'skills'          => SkillResource::collection($this->whenLoaded('skills')),
        ];
    }
}
