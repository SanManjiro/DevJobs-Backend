<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seules les entreprises peuvent créer une offre
        return $this->user()->isCompany();
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:150'],
            'description'      => ['required', 'string'],
            'location'         => ['nullable', 'string', 'max:100'],
            'type'             => ['required', 'in:full_time,part_time,freelance,stage'],
            'remote'           => ['sometimes', 'in:on_site,remote,hybrid'],
            'salary_min'       => ['nullable', 'integer', 'min:0'],
            'salary_max'       => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'experience_level' => ['required', 'in:junior,intermediaire,senior'],
            'status'           => ['sometimes', 'in:draft,published'],
            'expires_at'       => ['nullable', 'date', 'after:today'],
            // Skills attachés à l'offre (tableau optionnel)
            'skills'           => ['sometimes', 'array'],
            'skills.*.id'      => ['required_with:skills', 'integer', 'exists:skills,id'],
            'skills.*.required'=> ['boolean'],
        ];
    }
}
