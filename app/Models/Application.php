<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'developer_id', 'job_id', 'cover_letter', 'status',
    ];
}
