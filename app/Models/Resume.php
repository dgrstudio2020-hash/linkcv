<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'headline',
        'email',
        'phone',
        'location',
        'linkedin',
        'summary',
        'experiences',
        'skills',
        'template',
        'photo_url',
        'education',
        'languages',
        'additional_info',
    ];

    protected $casts = [
        'experiences' => 'array',
        'skills' => 'array',
        'education' => 'array',
        'languages' => 'array',
        'additional_info' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
