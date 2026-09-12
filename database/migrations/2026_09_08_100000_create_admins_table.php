<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Admins are their own accounts, not customers with a flag.
 *
 * The panel can read every celebration, every payment and every payout on the
 * platform, and it can sign in as a customer. Keeping those credentials in the
 * customers table meant one leaked password reused across both, one session
 * cookie for both, and no way to tell staff access apart from customer access
 * in an audit. This is a separate table behind its own guard.
 *
 * Anyone already carrying account_type = 'admin' is copied across as a super
 * admin so nobody is locked out by this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 120);
            $table->string('email', 190)->unique();
            $table->string('password');

            // A super admin holds every permission implicitly and is the only
            // one who can add, change or remove other admins.
            $table->boolean('is_super')->default(false);
            $table->enum('status', ['active', 'suspended'])->default('active');

            // Who created this account, for the audit trail. Nullable because
            // the first one is created from the console.
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();

            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            $table->rememberToken();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained()->cascadeOnDelete();

            // One row per granted permission. The catalogue of what these can
            // be lives in App\Models\Admin::PERMISSIONS, so adding a capability
            // is a code change and a review, not a stray INSERT.
            $table->string('permission', 60);

            $table->foreignId('granted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['admin_id', 'permission']);
            $table->index('permission');
        });

        $this->carryOverExistingAdmins();
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admins');
    }

    /**
     * Move anyone who was an admin under the old scheme into the new table,
     * password and all, so the panel is never left without a way in.
     */
    private function carryOverExistingAdmins(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'account_type')) {
            return;
        }

        DB::table('users')
            ->where('account_type', 'admin')
            ->orderBy('id')
            ->get(['first_name', 'last_name', 'email', 'password'])
            ->each(function ($user) {
                if (DB::table('admins')->where('email', $user->email)->exists()) {
                    return;
                }

                DB::table('admins')->insert([
                    'uuid'       => (string) Str::uuid(),
                    'name'       => trim("{$user->first_name} {$user->last_name}") ?: 'Admin',
                    'email'      => $user->email,
                    'password'   => $user->password,
                    'is_super'   => true,
                    'status'     => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }
};
