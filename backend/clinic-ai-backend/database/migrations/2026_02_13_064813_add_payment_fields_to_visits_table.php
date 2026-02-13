<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            // 会計金額
            $table->integer('payment_amount')->nullable()->after('exam_ended_at')->comment('会計金額（円）');
            
            // 保険種別
            $table->string('insurance_type', 50)->nullable()->after('payment_amount')->comment('保険種別（社保/国保/自費等）');
            
            // 会計準備完了タイムスタンプ
            $table->timestamp('payment_ready_at')->nullable()->after('insurance_type')->comment('会計準備完了日時');
            
            // 会計呼出タイムスタンプ
            $table->timestamp('payment_called_at')->nullable()->after('payment_ready_at')->comment('会計呼出日時');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['payment_amount', 'insurance_type', 'payment_ready_at', 'payment_called_at']);
        });
    }
};
