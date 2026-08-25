<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /v1/company/profile
     *
     * Le profil de l'entreprise connectée, dans la même forme que la fiche
     * publique (CompanyResource) mais sans les offres.
     */
    public function show(Request $request): CompanyResource
    {
        return new CompanyResource($request->user()->load('companyProfile'));
    }

    /**
     * PUT /v1/company/profile
     *
     * camelCase des deux côtés ; on ne mappe que `website` vers sa colonne (les
     * autres clés portent déjà le nom de leur colonne).
     */
    public function update(Request $request): CompanyResource
    {
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
            'industry'    => ['nullable', 'string', 'max:100'],
            'website'     => ['nullable', 'url', 'max:255'],
            'country'     => ['nullable', 'string', 'max:100'],
            'size'        => ['nullable', 'in:startup,pme,grande_entreprise'],
        ]);

        $user = $request->user();
        $user->companyProfile->update([
            'description' => $data['description'] ?? null,
            'industry'    => $data['industry'] ?? null,
            'website'     => $data['website'] ?? null,
            'country'     => $data['country'] ?? null,
            'size'        => $data['size'] ?? null,
        ]);

        return new CompanyResource($user->load('companyProfile'));
    }
}
