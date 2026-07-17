<?php

namespace App\Http\Controllers;

use App\Http\Resources\SkillResource;
use App\Models\Skill;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SkillController extends Controller
{
    /**
     * Liste la taxonomie des compétences.
     *
     * Alimente la sidebar de filtres et le formulaire de création d'offre, qui
     * codaient jusqu'ici leurs listes en dur.
     *
     * GET /api/v1/skills
     */
    public function index(): AnonymousResourceCollection
    {
        return SkillResource::collection(Skill::orderBy('name')->get());
    }
}
