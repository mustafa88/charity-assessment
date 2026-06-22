<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMember extends Model
{
    protected $fillable = [
        'assessment_id','name','dob','gender','school','needs_tutoring',
        'tutor_subject','higher_education','marital_status','contributes','is_orphan','is_eligible',
    ];

    protected $casts = [
        'dob'              => 'date',
        'needs_tutoring'   => 'boolean',
        'higher_education' => 'boolean',
        'contributes'      => 'boolean',
        'is_orphan'        => 'boolean',
        'is_eligible'      => 'boolean',
    ];

    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
}
