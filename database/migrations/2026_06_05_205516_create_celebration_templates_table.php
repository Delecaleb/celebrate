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
        Schema::create('celebration_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->string('description')->nullable();

            // Color palette
            $table->string('page_bg', 7)->default('#F9FAFB');
            $table->string('card_bg', 7)->default('#FFFFFF');
            $table->string('text_primary', 7)->default('#111827');
            $table->string('text_secondary', 7)->default('#6B7280');
            $table->string('accent_color', 7)->default('#F43F5E');

            // Cover photo border
            $table->string('photo_border_style', 10)->default('solid'); // none|solid|dashed|double
            $table->string('photo_border_color', 7)->default('#E5E7EB');
            $table->unsignedTinyInteger('photo_border_width')->default(2);

            // Wishes section layout
            $table->string('wishes_layout', 10)->default('scroll'); // scroll|grid

            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celebration_templates');
    }
};
