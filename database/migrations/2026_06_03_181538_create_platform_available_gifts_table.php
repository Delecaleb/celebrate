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
        Schema::create('platform_available_gifts', function (Blueprint $table) {
            $table->id();
            $table->string('gift_name');
            $table->text('gift_description')->nullable();
            $table->decimal('gift_price', 8, 2);
            $table->string('gift_image_url');
            $table->string('gift_link_url');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_available_gifts');
    }
};
