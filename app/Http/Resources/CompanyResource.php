<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Représente une entreprise (User de rôle "company" + son CompanyProfile).
 *
 * Liste blanche stricte : `email`, `is_active` et les timestamps du compte ne
 * sortent jamais d'ici. Ces routes sont publiques et non authentifiées.
 */
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // whenLoaded() renvoie un MissingValue (un objet), pas null : `?->` ne
        // le court-circuiterait pas. On veut le modèle ou null, franchement.
        $profile = $this->relationLoaded('companyProfile')
            ? $this->companyProfile
            : null;

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $profile?->description,
            'industry'    => $profile?->industry,
            'website'     => $profile?->website,
            'country'     => $profile?->country,
            'size'        => $profile?->size,
            // URL absolue construite ici : le client ne concatène jamais de base.
            'logoUrl'     => $profile?->logo_path
                ? Storage::url($profile->logo_path)
                : null,
            'jobCount'    => $this->whenCounted('jobListings'),
            'jobs'        => JobResource::collection($this->whenLoaded('jobListings')),
        ];
    }
}
