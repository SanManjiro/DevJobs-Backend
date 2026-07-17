<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            // Texte brut : le découpage en paragraphes est un choix d'affichage
            // et reste côté client.
            'description'     => $this->description,
            'location'        => $this->location,
            'type'            => $this->type,
            'remote'          => $this->remote,
            'experienceLevel' => $this->experience_level,
            'salaryMin'       => $this->salary_min,
            'salaryMax'       => $this->salary_max,
            'status'          => $this->status,
            'expiresAt'       => $this->expires_at?->toIso8601String(),
            'createdAt'       => $this->created_at?->toIso8601String(),
            'company'         => new CompanyResource($this->whenLoaded('company')),
            'skills'          => SkillResource::collection($this->whenLoaded('skills')),
            'applicationCount' => $this->whenCounted('applications'),
        ];
    }
}
