<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyController extends Controller
{
    /**
     * Liste toutes les entreprises actives avec leur profil.
     *
     * GET /api/v1/companies
     */
    public function index(): AnonymousResourceCollection
    {
        $companies = User::where('role', 'company')
            ->where('is_active', true)
            ->with('companyProfile')
            // jobCount ne compte que les offres qu'un visiteur peut réellement ouvrir.
            ->withCount(['jobListings' => fn ($q) => $q->published()->notExpired()])
            ->get();

        return CompanyResource::collection($companies);
    }

    /**
     * Affiche une entreprise spécifique avec ses offres actives.
     *
     * GET /api/v1/companies/{company}
     */
    public function show(User $company): CompanyResource
    {
        abort_if($company->role !== 'company', 404, 'Entreprise introuvable.');
        abort_if(! $company->is_active, 404, 'Entreprise introuvable.');

        $company->load([
            'companyProfile',
            'jobListings' => fn ($q) => $q->published()->notExpired()->with('skills'),
        ]);
        $company->loadCount(['jobListings' => fn ($q) => $q->published()->notExpired()]);

        return new CompanyResource($company);
    }
}
