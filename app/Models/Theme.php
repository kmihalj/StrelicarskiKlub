<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'theme_key',
        'variant',
        'description',
        'is_active',
        'colors',
        'logo_path',
        'favicon_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'colors' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
