<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model SiteSetting predstavlja zapis baze podataka i definira relacije te pomoćne metode za rad s podacima.
 */
class SiteSetting extends Model
{
    protected $fillable = [
        'theme_mode_policy',
        'payment_tracking_enabled',
        'payment_info_clanak_id',
        'school_tuition_adult_amount',
        'school_tuition_minor_amount',
        'logo_path',
        'logo_dark_path',
        'favicon_path',
    ];

    protected $casts = [
        'payment_tracking_enabled' => 'boolean',
        'payment_info_clanak_id' => 'integer',
        'school_tuition_adult_amount' => 'decimal:2',
        'school_tuition_minor_amount' => 'decimal:2',
    ];
}
