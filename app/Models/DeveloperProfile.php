<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeveloperProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'bio', 'location', 'github_url',
        'portfolio_url', 'cv_path', 'years_experience',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'developer_skill')
                    ->withPivot('level');
    }
}
