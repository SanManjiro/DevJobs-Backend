<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class OverviewController extends Controller
{
    /**
     * GET /v1/admin/overview
     *
     * Les compteurs de la page d'accueil admin. camelCase pour rester dans le
     * contrat, même si ce sont de simples entiers.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'users'        => User::count(),
                'jobs'         => JobListing::count(),
                'applications' => Application::count(),
            ],
        ]);
    }
}
