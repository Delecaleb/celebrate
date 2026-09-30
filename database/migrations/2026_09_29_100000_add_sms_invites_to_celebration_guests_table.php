<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SMS invitations.
 *
 * When an owner invites people from their phone's contacts, each one becomes a
 * guest row carrying the exact text they are to receive. The row is the
 * outbox: whatever sends the SMS takes rows with sms_status = 'pending', sends
 * invite_message to guest_phone, and records the outcome here — so "did Ada get
 * her invite?" is a query, not a guess.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('celebration_guests', function (Blueprint $table) {
            // Rendered when the invite is saved, so what the owner previewed is
            // what goes out, even if the celebration is renamed afterwards.
            $table->text('invite_message')->nullable()->after('invite_token');

            // pending → sent | failed. Null for guests added some other way,
            // who are not owed an SMS.
            $table->string('sms_status', 12)->nullable()->after('invite_message');
            $table->timestamp('sms_sent_at')->nullable()->after('sms_status');
            $table->text('sms_error')->nullable()->after('sms_sent_at');

            // The sender's query: what is still waiting, oldest first.
            $table->index(['sms_status', 'created_at']);
            // One invite per number per celebration.
            $table->index(['celebration_id', 'guest_phone']);
        });
    }

    public function down(): void
    {
        Schema::table('celebration_guests', function (Blueprint $table) {
            $table->dropIndex(['sms_status', 'created_at']);
            $table->dropIndex(['celebration_id', 'guest_phone']);
            $table->dropColumn(['invite_message', 'sms_status', 'sms_sent_at', 'sms_error']);
        });
    }
};
