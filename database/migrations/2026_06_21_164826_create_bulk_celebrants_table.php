<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bulk_celebrants', function (Blueprint $table) {
            $table->id();
            $table->uuid('organisation_uuid');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('celebration_type');
            $table->date('celebration_date');
            $table->string('photo_path')->nullable();
            $table->boolean('processed')->default(false);
            $table->date('next_occurrence')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_celebrants');
    }
};
?>
