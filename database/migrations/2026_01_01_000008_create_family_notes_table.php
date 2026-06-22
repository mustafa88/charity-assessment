<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // سجل ملاحظات تراكمي للعائلة — كل ملاحظة محفوظة بتاريخ كتابتها (توثيق ما يجري ويتمّ).
    public function up(): void
    {
        Schema::create('family_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // كاتب الملاحظة
            $table->text('body');
            $table->timestamps(); // created_at = تاريخ كتابة الملاحظة
        });
    }

    public function down(): void { Schema::dropIfExists('family_notes'); }
};
