<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * إزالة عمود assessments.marital_status القديم — المصدر المعتمد للحالة الاجتماعية
     * هو families.marital_status (يقرأه ScoringEngine وكل الواجهات).
     */
    public function up(): void
    {
        // idempotent: قد يكون العمود أُزيل مسبقاً أثناء إعادة بناء الجدول.
        if (Schema::hasColumn('assessments', 'marital_status')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->dropColumn('marital_status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('marital_status', ['married', 'divorced', 'widowed', 'abandoned'])
                ->default('married')->after('house_type');
        });
    }
};
