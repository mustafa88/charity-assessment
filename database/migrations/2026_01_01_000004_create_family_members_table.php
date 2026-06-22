<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['m','f'])->default('m');
            $table->string('school')->nullable();
            $table->boolean('needs_tutoring')->default(false);
            $table->string('tutor_subject')->nullable();
            $table->boolean('higher_education')->default(false);
            $table->string('marital_status')->nullable();
            $table->boolean('contributes')->default(false);     // يعمل ويساهم في مصروف البيت
            $table->boolean('is_orphan')->default(false);        // يتيم — يُضبط آلياً (أرمل + عمر<15) ويُزال يدوياً فقط
            $table->boolean('is_eligible')->default(false);      // computed by ScoringEngine
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('family_members'); }
};
