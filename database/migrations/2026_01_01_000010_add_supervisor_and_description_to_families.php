<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            // المسؤول عن العائلة — قد يبقى فارغاً حتى قبول العائلة ثم يُحدَّد/يُعدَّل.
            $table->foreignId('supervisor_id')->nullable()->after('bank_name')
                ->constrained('supervisors')->nullOnDelete();
            // وصف حرّ للعائلة (اختياري) — يُدخَل عند فتح تقييم جديد أو التعديل.
            $table->text('description')->nullable()->after('supervisor_id');
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropColumn('description');
        });
    }
};
