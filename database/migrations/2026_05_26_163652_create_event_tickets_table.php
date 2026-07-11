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
        Schema::create('event_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('celebration_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_name');
            $table->decimal('price', 14, 2);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('sold_count')->default(0);
            $table->dateTime('sales_start')->nullable();
            $table->dateTime('sales_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_tickets');
    }
};
