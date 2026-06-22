<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = [
        'wife_name','husband_name','wife_id','husband_id','wife_dob','husband_dob',
        'marital_status','wife_phone','husband_phone','health_fund','bank_name','joint_account',
        'supervisor_id','description',
    ];

    protected $casts = [
        'wife_dob'      => 'date',
        'husband_dob'   => 'date',
        'joint_account' => 'boolean',
    ];

    public function supervisor(): BelongsTo { return $this->belongsTo(Supervisor::class); }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class)->latest('visit_date');
    }

    /** سجل الملاحظات التراكمي — الأحدث أولاً. */
    public function notes(): HasMany
    {
        return $this->hasMany(FamilyNote::class)->latest();
    }

    /** المرفقات (صور/PDF) — الأحدث أولاً. */
    public function attachments(): HasMany
    {
        return $this->hasMany(FamilyAttachment::class)->latest();
    }
}
