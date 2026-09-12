<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What staff did, kept where staff cannot be its own audience.
 *
 * Impersonation was previously written to activity_logs against the customer's
 * own id — the wrong home twice over: it sat in the customer's activity
 * relation waiting to be rendered to them, and it was invisible to the person
 * who actually needs it, the super admin.
 *
 * These rows are append-only by intent: no updated_at, and the admin's email is
 * copied in so an entry still names who did it after that account is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();

            // Null when the admin account is later removed — the entry stays.
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('admin_email', 190);

            $table->string('action', 60);
            $table->text('description');

            // What it was done to: a user, a withdrawal, a payment reference.
            $table->nullableMorphs('subject');
            $table->string('subject_label', 190)->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
            $table->index('created_at');
        });

        $this->moveImpersonationEntriesOut();
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }

    /**
     * Anything already written into the customer's activity feed by the old
     * impersonation code is moved here and deleted from there — leaving it in
     * place would keep it on the customer's account.
     */
    private function moveImpersonationEntriesOut(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        DB::table('activity_logs')
            ->where('action', 'like', 'admin.impersonation.%')
            ->orderBy('id')
            ->get()
            ->each(function ($entry) {
                // The old rows named the admin only inside the description.
                preg_match('/Admin (\S+@\S+)/', (string) $entry->description, $matches);

                DB::table('admin_audit_logs')->insert([
                    'admin_id'      => null,
                    'admin_email'   => $matches[1] ?? 'unknown',
                    'action'        => $entry->action,
                    'description'   => $entry->description,
                    'subject_type'  => \App\Models\User::class,
                    'subject_id'    => $entry->user_id,
                    'subject_label' => null,
                    'ip_address'    => $entry->ip_address,
                    'user_agent'    => $entry->user_agent,
                    'created_at'    => $entry->created_at ?? now(),
                ]);
            });

        DB::table('activity_logs')->where('action', 'like', 'admin.impersonation.%')->delete();
    }
};
