<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // مرفقات العائلة — صور أو ملفات PDF تُحفظ على القرص (disk: local) وتُخدَم خلف auth عبر السيرفر.
    public function up(): void
    {
        Schema::create('family_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // من رفع الملف
            $table->string('original_name');   // اسم الملف الأصلي (للعرض والتنزيل)
            $table->string('description')->nullable(); // وصف حرّ يُكتب وقت الرفع
            $table->string('path');            // المسار النسبي على القرص local
            $table->string('mime');            // image/jpeg | image/png | application/pdf …
            $table->unsignedBigInteger('size'); // بايت
            $table->timestamps();              // created_at = تاريخ الرفع
        });
    }

    public function down(): void { Schema::dropIfExists('family_attachments'); }
};
