<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    /**
     * Liste toutes les entreprises actives avec leur profil.
     *
     * GET /api/v1/companies
     */
    public function index(): JsonResponse
    {
        $companies = User::where('role', 'company')
            ->where('is_active', true)
            ->with('companyProfile')
            ->get();

        return response()->json($companies);
    }

    /**
     * Affiche une entreprise spécifique avec ses offres actives.
     *
     * GET /api/v1/companies/{company}
     */
    public function show(User $company): JsonResponse
    {
        if ($company->role !== 'company') {
            return response()->json(['message' => 'Entreprise introuvable.'], 404);
        }

        $company->load([
            'companyProfile',
            'jobListings' => fn($q) => $q->published()->notExpired()->with('skills'),
        ]);

        return response()->json($company);
    }
}
