<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // هوية العائلة الثابتة. المعطيات المتغيّرة (الوضع المالي، الحالة المعمارية...) في جدول التقييمات.
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('wife_name')->nullable();
            $table->string('husband_name')->nullable();
            // فريدة على مستوى القاعدة (NULL مسموح للحقول الفارغة — لا يُحتسب تكراراً).
            $table->string('wife_id')->nullable()->unique();
            $table->string('husband_id')->nullable()->unique();
            $table->date('wife_dob')->nullable();
            $table->date('husband_dob')->nullable();
            $table->enum('marital_status', ['married', 'divorced', 'widowed', 'abandoned'])->default('married');
            $table->string('wife_phone')->nullable()->unique();
            $table->string('husband_phone')->nullable()->unique();
            $table->string('health_fund')->nullable();
            $table->string('bank_name')->nullable();
            $table->boolean('joint_account')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('families'); }
};
