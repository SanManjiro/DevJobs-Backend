<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active',
        'provider', 'provider_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    // Relations
    public function developerProfile(): HasOne
    {
        return $this->hasOne(DeveloperProfile::class);
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function jobListings(): HasMany
    {
        return $this->hasMany(JobListing::class, 'company_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'developer_id');
    }

    public function savedJobs(): BelongsToMany
    {
        return $this->belongsToMany(JobListing::class, 'saved_jobs', 'developer_id', 'job_id')
                    ->withTimestamps();
    }

    // Helpers
    public function isDeveloper(): bool { return $this->role === 'developer'; }
    public function isCompany(): bool   { return $this->role === 'company'; }
    public function isAdmin(): bool     { return $this->role === 'admin'; }
}
