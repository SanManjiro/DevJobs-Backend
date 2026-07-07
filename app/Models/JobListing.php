<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    protected $fillable = [
        'company_id', 'title', 'description', 'location',
        'type', 'remote', 'salary_min', 'salary_max',
        'experience_level', 'status', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'salary_min' => 'integer',
        'salary_max' => 'integer',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}
