<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('home_needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->string('item');     // ثلاجة, غاز, ...
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('home_needs'); }
};
