<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoringPolicy extends Model
{
    protected $fillable = [
        'version','is_active','approval_threshold','rent_bonus','marital_bonus',
        'per_eligible_person','bands','missing_group_size','missing_group_points',
        'arch_points','effective_from',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'approval_threshold' => 'decimal:2',
        'bands'              => 'array',
        'arch_points'        => 'array',
        'effective_from'     => 'date',
    ];

    public static function active(): self
    {
        return static::where('is_active', true)->latest('version')->firstOrFail();
    }
}
