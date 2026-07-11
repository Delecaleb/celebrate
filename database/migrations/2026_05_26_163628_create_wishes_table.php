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
        Schema::create('wishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('celebration_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->enum('wish_type', ['cash', 'item', 'experience', 'service'])->default('cash');
            $table->decimal('target_amount', 14, 2)->nullable();
            $table->decimal('current_amount', 14, 2)->nullable()->default(0);
            $table->string('currency')->nullable();
            $table->string('wish_image')->nullable();
            $table->string('wish_link')->nullable();
            $table->enum('priority_level', ['low', 'medium', 'high'])->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->boolean('allow_partial_contribution')->default(true);
            $table->unsignedInteger('contribution_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishes');
    }
};
