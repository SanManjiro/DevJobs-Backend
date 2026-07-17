<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\MapsJobPayload;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobListingRequest extends FormRequest
{
    use MapsJobPayload;

    public function authorize(): bool
    {
        // Seules les entreprises peuvent créer une offre.
        return $this->user()->isCompany();
    }

    /**
     * Le contrat est camelCase des deux côtés : les clés du bag 422 sont ainsi
     * les mêmes que les `name` des champs du formulaire.
     */
    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:150'],
            'description'     => ['required', 'string'],
            'location'        => ['nullable', 'string', 'max:100'],
            'type'            => ['required', 'in:full_time,part_time,freelance,stage'],
            'remote'          => ['sometimes', 'in:on_site,remote,hybrid'],
            'salaryMin'       => ['nullable', 'integer', 'min:0'],
            'salaryMax'       => ['nullable', 'integer', 'min:0', 'gte:salaryMin'],
            'experienceLevel' => ['required', 'in:junior,intermediaire,senior'],
            'status'          => ['sometimes', 'in:draft,published'],
            'expiresAt'       => ['nullable', 'date', 'after:today'],
            'skills'          => ['sometimes', 'array'],
            'skills.*.id'     => ['required_with:skills', 'integer', 'exists:skills,id'],
            'skills.*.required' => ['boolean'],
        ];
    }
}
