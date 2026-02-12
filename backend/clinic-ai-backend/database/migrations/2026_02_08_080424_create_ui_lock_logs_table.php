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
        Schema::create('ui_lock_logs', function (Blueprint $table) {
        $table->id();
        $table->string('ui_name');
        $table->string('action');
        $table->string('operator');
        $table->text('reason')->nullable();
        $table->timestamp('occurred_at')->useCurrent();
        $table->timestamp('created_at');

        $table->index('ui_name');
        $table->index('action');
        $table->index('operator');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ui_lock_logs');
    }
};
