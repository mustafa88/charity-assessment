<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finance extends Model
{
    protected $fillable = ['assessment_id','type','category','amount','is_bimonthly','notes'];

    protected $casts = [
        'amount'       => 'decimal:2',
        'is_bimonthly' => 'boolean',
    ];

    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
}
