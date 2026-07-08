<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Vérifie que l'utilisateur est bien le propriétaire de l'offre
        $job = $this->route('job');
        return $this->user()->isCompany() && $job->company_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'title'            => ['sometimes', 'string', 'max:150'],
            'description'      => ['sometimes', 'string'],
            'location'         => ['nullable', 'string', 'max:100'],
            'type'             => ['sometimes', 'in:full_time,part_time,freelance,stage'],
            'remote'           => ['sometimes', 'in:on_site,remote,hybrid'],
            'salary_min'       => ['nullable', 'integer', 'min:0'],
            'salary_max'       => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'experience_level' => ['sometimes', 'in:junior,intermediaire,senior'],
            'status'           => ['sometimes', 'in:draft,published'],
            'expires_at'       => ['nullable', 'date', 'after:today'],
            'skills'           => ['sometimes', 'array'],
            'skills.*.id'      => ['required_with:skills', 'integer', 'exists:skills,id'],
            'skills.*.required'=> ['boolean'],
        ];
    }
}
