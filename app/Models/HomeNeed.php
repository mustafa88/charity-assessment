<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeNeed extends Model
{
    protected $fillable = ['assessment_id','item'];

    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
}
