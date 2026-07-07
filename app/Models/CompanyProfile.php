<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    protected $fillable = [
        'user_id', 'description', 'industry',
        'website', 'logo_path', 'country', 'size',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
