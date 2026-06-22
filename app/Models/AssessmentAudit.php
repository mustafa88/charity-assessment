<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAudit extends Model
{
    public $timestamps = false;   // created_at فقط

    protected $fillable = [
        'assessment_id','action','from_version','to_version','from_score','to_score',
        'from_decision','to_decision','user_id','meta',
    ];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];
}
