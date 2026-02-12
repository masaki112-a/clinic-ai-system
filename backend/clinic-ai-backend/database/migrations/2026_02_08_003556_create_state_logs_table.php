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
        Schema::create('state_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained();
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->string('reason')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_logs');
    }
};
