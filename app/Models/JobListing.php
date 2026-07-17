<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobListing extends Model
{
    use HasFactory;

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skill')
                    ->withPivot('required');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'job_id');
    }

    public function savedByDevelopers(): BelongsToMany
    {
        // Voir User::savedJobs() : saved_jobs ne porte qu'un created_at.
        return $this->belongsToMany(User::class, 'saved_jobs', 'job_id', 'developer_id')
                    ->withPivot('created_at');
    }

    // Scopes
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
