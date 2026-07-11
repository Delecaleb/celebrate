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
        Schema::create('wish_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('celebration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contributor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contributor_name')->nullable();
            $table->string('contributor_email')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->enum('contribution_type', ['cash', 'direct_purchase', 'promise'])->default('cash');
            $table->text('message')->nullable();
            $table->string('payment_reference')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wish_contributions');
    }
};
