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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            // Amount in base currency (USD) — the canonical ledger unit
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('USD');
            // Original amount the user paid/received in their local currency
            $table->decimal('original_amount', 14, 2)->nullable();
            $table->string('original_currency', 10)->nullable();
            $table->string('description');
            $table->string('reference')->unique();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            // Polymorphic relation to the source (Gift, WishContribution, etc.)
            $table->string('transactionable_type')->nullable();
            $table->unsignedBigInteger('transactionable_id')->nullable();
            $table->index(['transactionable_type', 'transactionable_id'], 'wt_morphs_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
