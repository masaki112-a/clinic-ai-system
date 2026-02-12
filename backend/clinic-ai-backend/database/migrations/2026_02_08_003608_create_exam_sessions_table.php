<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();

            // Issue6：Visit（来院）との紐付け
            $table->foreignId('visit_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('current_state')
                ->comment('idle / calling / in_exam / finished');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->string('ai_config_version')->nullable()
                ->comment('prompt/dictionary snapshot id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
