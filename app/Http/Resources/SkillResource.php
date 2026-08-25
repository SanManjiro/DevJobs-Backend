<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillResource extends JsonResource
{
    /**
     * Aplatit le pivot job_skill : le client reçoit {id, name, required}
     * et n'a jamais à connaître la table pivot.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'required' => $this->whenPivotLoaded(
                'job_skill',
                fn () => (bool) $this->pivot->required,
            ),
            'level' => $this->whenPivotLoaded(
                'developer_skill',
                fn () => $this->pivot->level,
            ),
        ];
    }
}
