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
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('reactionable_id');
            $table->string('reactionable_type');
            $table->enum('reaction_type', ['like', 'love', 'clap', 'fire'])->default('like');
            $table->string('guest_identifier')->nullable();
            $table->timestamps();
            $table->index(['reactionable_id', 'reactionable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
