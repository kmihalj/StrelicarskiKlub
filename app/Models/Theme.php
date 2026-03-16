<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Theme predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
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

    /**
     * Scope koji vraća samo aktivne teme kako bi se izbjeglo ručno ponavljanje uvjeta `is_active = true`.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
