<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyAttachment extends Model
{
    protected $fillable = ['family_id', 'user_id', 'original_name', 'description', 'path', 'mime', 'size'];

    public function family(): BelongsTo { return $this->belongsTo(Family::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    /** هل المرفق صورة (لعرض معاينة مصغّرة)؟ */
    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }
}
