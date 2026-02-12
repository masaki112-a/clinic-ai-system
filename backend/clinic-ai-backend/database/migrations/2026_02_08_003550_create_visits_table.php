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
       Schema::create('visits', function (Blueprint $table) {
            $table->id();

            $table->string('visit_code')->unique(); // QR / IC
            $table->string('current_state')->index(); // S0〜S9

            // 診察なし会計フラグ（憲法第10条：例外遷移対応）
            $table->boolean('is_no_exam')->default(false);
            
            // 再呼出回数（憲法第10条：S5遷移対応）
            $table->unsignedInteger('recall_count')->default(0);

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('exam_started_at')->nullable();
            $table->timestamp('exam_ended_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('ended_at')->nullable(); // 完了時刻

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
