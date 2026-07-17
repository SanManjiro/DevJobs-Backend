<?php

namespace App\Http\Requests\Concerns;

trait MapsJobPayload
{
    /**
     * Traduit le payload camelCase validé vers les colonnes snake_case du
     * modèle, en ne gardant que les clés réellement fournies (indispensable
     * pour une mise à jour partielle : une clé absente ne doit pas écraser).
     * `skills` passe tel quel — le service sait le synchroniser.
     */
    public function mapped(): array
    {
        $data = $this->validated();

        $columns = [
            'title'           => 'title',
            'description'     => 'description',
            'location'        => 'location',
            'type'            => 'type',
            'remote'          => 'remote',
            'status'          => 'status',
            'salaryMin'       => 'salary_min',
            'salaryMax'       => 'salary_max',
            'experienceLevel' => 'experience_level',
            'expiresAt'       => 'expires_at',
        ];

        $out = [];
        foreach ($columns as $in => $col) {
            if (array_key_exists($in, $data)) {
                $out[$col] = $data[$in];
            }
        }

        if (array_key_exists('skills', $data)) {
            $out['skills'] = $data['skills'];
        }

        return $out;
    }
}
