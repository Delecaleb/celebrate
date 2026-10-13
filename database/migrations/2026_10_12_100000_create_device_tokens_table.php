<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Expo push tokens, one row per installed app. A person signed in on two phones
| has two rows; a phone that changes hands moves its row to the new account.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform', 16)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        // Every push reminder is sent once per celebration per step — this is
        // where the commands remember what they have already sent.
        Schema::create('push_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 48);
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();

            $table->unique(['user_id', 'kind', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_reminders');
        Schema::dropIfExists('device_tokens');
    }
};
