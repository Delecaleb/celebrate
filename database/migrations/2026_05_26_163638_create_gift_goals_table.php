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
        Schema::create('gift_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('celebration_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('target_amount', 14, 2);
            $table->decimal('current_amount', 14, 2)->default(0);
            $table->string('cover_image')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->enum('status', ['active', 'completed', 'closed'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_goals');
    }
};
