<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // سجل تدقيق: مَن غيّر، متى، الإصدار/النقاط/القرار من→إلى. لا تعديل ولا حذف.
        Schema::create('assessment_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->string('action');                 // converted_policy | decided | revisited (تغيّر تاريخ الزيارة، التفاصيل في meta)
            $table->unsignedInteger('from_version')->nullable();
            $table->unsignedInteger('to_version')->nullable();
            $table->integer('from_score')->nullable();
            $table->integer('to_score')->nullable();
            $table->string('from_decision')->nullable();
            $table->string('to_decision')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void { Schema::dropIfExists('assessment_audits'); }
};
