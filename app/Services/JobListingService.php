<?php

namespace App\Services;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class JobListingService
{
    /**
     * Retourne la liste paginée des offres publiées avec filtres optionnels.
     */
    public function list(Request $request): LengthAwarePaginator
    {
        $query = JobListing::query()
            ->published()
            ->notExpired()
            ->with(['company.companyProfile', 'skills']);

        // Filtre par type de contrat
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtre par mode de travail
        if ($request->filled('remote')) {
            $query->where('remote', $request->remote);
        }

        // Filtre par niveau d'expérience
        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->experience_level);
        }

        // Filtre par localisation (recherche partielle)
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Recherche par titre ou description
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filtre par compétence requise
        if ($request->filled('skill')) {
            $query->whereHas('skills', fn ($q) => $q->where('name', $request->skill));
        }

        return $query->latest()->paginate(15);
    }

    /**
     * Crée une nouvelle offre et attache les compétences si fournies.
     */
    public function create(User $company, array $data): JobListing
    {
        $skills = $data['skills'] ?? [];
        unset($data['skills']);

        $job = $company->jobListings()->create($data);

        if (! empty($skills)) {
            $this->syncSkills($job, $skills);
        }

        return $job->load(['company.companyProfile', 'skills']);
    }

    /**
     * Met à jour une offre et re-synchronise les compétences si fournies.
     */
    public function update(JobListing $job, array $data): JobListing
    {
        $skills = $data['skills'] ?? null;
        unset($data['skills']);

        $job->update($data);

        if ($skills !== null) {
            $this->syncSkills($job, $skills);
        }

        return $job->fresh(['company.companyProfile', 'skills']);
    }

    /**
     * Supprime une offre d'emploi.
     */
    public function delete(JobListing $job): void
    {
        $job->delete();
    }

    /**
     * Synchronise le pivot job_skill avec les compétences fournies.
     *
     * @param array $skills  [['id' => 1, 'required' => true], ...]
     */
    private function syncSkills(JobListing $job, array $skills): void
    {
        $pivot = [];
        foreach ($skills as $skill) {
            $pivot[$skill['id']] = ['required' => $skill['required'] ?? true];
        }
        $job->skills()->sync($pivot);
    }
}
