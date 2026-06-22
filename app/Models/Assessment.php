<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $fillable = [
        'family_id','scoring_policy_id','visit_date','visitors','next_visit_date','house_type',
        'has_orphans','needs_repair','arch_condition','house_location',
        'repairs_notes','total_score','per_person_remaining','recommended',
        'decision','decision_note','decided_at',
    ];

    protected $casts = [
        'visit_date'           => 'date',
        'next_visit_date'      => 'date',
        'has_orphans'          => 'boolean',
        'needs_repair'         => 'boolean',
        'recommended'          => 'boolean',
        'per_person_remaining' => 'decimal:2',
        'decided_at'           => 'datetime',
    ];

    public function family(): BelongsTo       { return $this->belongsTo(Family::class); }
    public function policy(): BelongsTo       { return $this->belongsTo(ScoringPolicy::class, 'scoring_policy_id'); }
    public function members(): HasMany        { return $this->hasMany(FamilyMember::class); }
    public function finances(): HasMany       { return $this->hasMany(Finance::class); }
    public function homeNeeds(): HasMany      { return $this->hasMany(HomeNeed::class); }
    public function audits(): HasMany         { return $this->hasMany(AssessmentAudit::class); }
}
