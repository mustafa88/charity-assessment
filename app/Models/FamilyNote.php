<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyNote extends Model
{
    protected $fillable = ['family_id','user_id','body'];

    public function family(): BelongsTo { return $this->belongsTo(Family::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
