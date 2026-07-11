<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->nullOnDelete();
            $table->decimal('amount', 12, 2);             // base currency (USD)
            $table->string('currency', 3)->default('USD');
            $table->decimal('original_amount', 12, 2)->nullable();  // user's local currency
            $table->string('original_currency', 3)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'rejected'])
                  ->default('pending');
            $table->string('reference')->unique();
            $table->string('note', 500)->nullable();      // admin note or failure reason
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
