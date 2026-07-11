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
        Schema::create('celebrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('celebration_type', ['birthday', 'wedding', 'memorial', 'graduation', 'anniversary', 'other'])->default('birthday');
            $table->string('celebrant_name');
            $table->string('celebrant_photo')->nullable();
            $table->longText('description')->nullable();
            $table->dateTime('event_date')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('venue')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('allow_wishes')->default(true);
            $table->boolean('allow_gifts')->default(true);
            $table->boolean('allow_media_uploads')->default(false);
            $table->boolean('allow_guest_posts')->default(false);
            $table->string('theme_color')->nullable();
            $table->string('font_style')->nullable();
            $table->string('cover_photo')->nullable();
            $table->string('intro_video')->nullable();
            $table->string('background_music')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celebrations');
    }
};
