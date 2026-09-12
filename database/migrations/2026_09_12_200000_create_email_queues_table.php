<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The outbox.
 *
 * Mail used to go into Laravel's jobs table, where it was a serialised blob:
 * you could count the rows and nothing else. When twelve gift receipts sat
 * there unsent for a day, nothing in the panel could say who they were for or
 * what they said.
 *
 * This table holds the mail itself — who it is for, what it says, what the
 * server answered — so a stuck or refused email is something an operator can
 * read, retry, and explain to the person who did not get it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_queues', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // What kind of mail this is: gift.received, gift.sent,
            // celebration.reminder. Free text on purpose — a new mail should
            // not need a migration.
            $table->string('type', 64)->index();
            $table->string('mailable')->nullable();

            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->string('to_address');
            $table->string('to_name')->nullable();

            $table->string('subject');
            // Rendered when it is queued, not when it is sent: a template that
            // cannot render fails in front of the person who caused it rather
            // than silently, minutes later, in a worker.
            $table->longText('body_html');

            // Whatever the sender wants to find it by later — a celebration id,
            // a payment reference, the gift ids in a basket.
            $table->json('metadata')->nullable();

            $table->string('status', 12)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);

            // When it may next be tried. Also how "send this later" works.
            $table->timestamp('available_at')->nullable();
            // Held by whichever worker claimed it, so two cannot send it twice.
            $table->timestamp('reserved_at')->nullable();

            $table->text('response')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            // The sender's query: what is due, oldest first.
            $table->index(['status', 'available_at']);
            $table->index(['to_address', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_queues');
    }
};
