<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supervisor extends Model
{
    protected $fillable = ['name', 'phone'];

    public function families(): HasMany { return $this->hasMany(Family::class); }
}
