<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeveloperProfile extends Model
{
    protected $fillable = [
        'user_id', 'bio', 'location', 'github_url',
        'portfolio_url', 'cv_path', 'years_experience',
    ];
}
