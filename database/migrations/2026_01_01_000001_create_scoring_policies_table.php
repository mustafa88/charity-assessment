<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scoring_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version')->unique();
            $table->boolean('is_active')->default(false);

            $table->decimal('approval_threshold', 10, 2);   // المتبقي للفرد المؤهِّل
            $table->integer('rent_bonus');
            $table->integer('marital_bonus');
            $table->integer('per_eligible_person');
            $table->json('bands');                          // [{max:500,points:3},...]
            $table->integer('missing_group_size');
            $table->integer('missing_group_points');
            $table->json('arch_points');                    // [0,1,2,3]

            $table->date('effective_from')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('scoring_policies'); }
};
