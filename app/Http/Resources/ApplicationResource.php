<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une candidature vue par le développeur qui l'a envoyée. camelCase, comme tout
 * le contrat. Le poste est imbriqué (JobResource) quand il est chargé — c'est
 * lui qui porte le titre et l'entreprise affichés dans la liste.
 */
class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'coverLetter' => $this->cover_letter,
            'appliedAt'   => $this->created_at?->toIso8601String(),
            'job'         => new JobResource($this->whenLoaded('job')),
        ];
    }
}
