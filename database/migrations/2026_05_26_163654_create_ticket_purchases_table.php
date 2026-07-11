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
        Schema::create('ticket_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('event_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('total_amount', 14, 2);
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('qr_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_purchases');
    }
};
