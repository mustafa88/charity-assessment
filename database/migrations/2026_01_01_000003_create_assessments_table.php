<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // كل زيارة = تقييم مستقل يحمل لقطة السياسة + النتيجة المحسوبة + القرار اليدوي.
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scoring_policy_id')->constrained();   // لقطة الإصدار وقت التقييم

            $table->date('visit_date')->nullable();
            $table->string('visitors')->nullable();
            $table->date('next_visit_date')->nullable(); // افتراضياً: تاريخ الزيارة + 6 أشهر

            // معطيات تؤثر في الحساب (قد تتغيّر بين زيارة وأخرى)
            $table->enum('house_type', ['own','rent','family','other'])->default('own');
            $table->boolean('has_orphans')->default(false);
            $table->boolean('needs_repair')->default(false);
            $table->unsignedTinyInteger('arch_condition')->default(0); // 0..3
            $table->text('house_location')->nullable();
            $table->text('repairs_notes')->nullable();

            // نتائج محسوبة (snapshot) — مصدرها ScoringEngine
            $table->integer('total_score')->default(0);
            $table->decimal('per_person_remaining', 10, 2)->default(0);
            $table->boolean('recommended')->default(false);          // التوصية الآلية

            // القرار النهائي اليدوي — منفصل تماماً عن التوصية
            $table->enum('decision', ['pending','accepted','rejected'])->default('pending');
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('assessments'); }
};
