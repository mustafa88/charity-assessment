<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // سطر واحد لكل بند (مرن: يمكن إضافة بنود مستقبلاً دون تعديل المخطط).
        Schema::create('finances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['expense','income']);
            $table->string('category');               // rent, electric, father, ...
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_bimonthly')->default(false);  // يُقسَّم على 2 للشهري
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('finances'); }
};
