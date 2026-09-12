<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Create the first admin, or add one from the server.
 *
 * The panel itself can add staff — this exists for the first account on a new
 * box, and for the day somebody locks themselves out.
 *
 *   php artisan admin:create
 *   php artisan admin:create ops@celebratemi.com --name="Ada Okafor" --super
 *   php artisan admin:create ops@celebratemi.com --permissions=users.view,payments.view
 *   php artisan admin:create ops@celebratemi.com --suspend
 */
class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {email? : The admin to create or update}
                            {--name= : Display name}
                            {--password= : Skip the prompt (visible in shell history — prefer the prompt)}
                            {--super : Grant every permission, now and in future}
                            {--permissions= : Comma-separated list, for a non-super admin}
                            {--suspend : Suspend this admin instead}
                            {--list : Show the permission catalogue and exit}';

    protected $description = 'Create or update an admin account for the panel';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listPermissions();
        }

        $email = strtolower(trim((string) ($this->argument('email') ?: $this->ask('Email address'))));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $admin = Admin::where('email', $email)->first();

        if ($this->option('suspend')) {
            return $this->suspend($admin, $email);
        }

        return $admin ? $this->update($admin) : $this->create($email);
    }

    private function listPermissions(): int
    {
        $this->table(
            ['Permission', 'Group', 'What it allows'],
            collect(Admin::PERMISSIONS)
                ->map(fn (array $meta, string $key) => [$key, $meta['group'], $meta['note']])
                ->values()
                ->all()
        );

        return self::SUCCESS;
    }

    private function create(string $email): int
    {
        $name     = $this->option('name') ?: $this->ask('Full name');
        $password = $this->option('password') ?: $this->secret('Password (at least 12 characters)');

        $check = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::min(12)->letters()->numbers()]]
        );

        if ($check->fails()) {
            $this->error($check->errors()->first('password'));

            return self::FAILURE;
        }

        $isSuper = (bool) $this->option('super');

        // The very first admin has to be a super admin, or there is nobody who
        // can add the second one.
        if (! $isSuper && Admin::count() === 0) {
            $this->warn('This is the first admin account, so it is being made a super admin — otherwise nobody could add the next one.');
            $isSuper = true;
        }

        $admin = Admin::create([
            'name'     => $name ?: 'Admin',
            'email'    => $email,
            'password' => $password,
            'is_super' => $isSuper,
            'status'   => 'active',
        ]);

        $admin->syncPermissions($this->requestedPermissions());

        $this->newLine();
        $this->info("Admin created: {$admin->email}");
        $this->line('Sign in at ' . route('admin.login'));
        $this->line($isSuper ? 'Access: super admin (everything)' : 'Access: ' . $this->describe($admin));
        $this->newLine();
        $this->warn('This account can read every celebration, payment and payout on the platform. One account per person, and never shared.');

        return self::SUCCESS;
    }

    private function update(Admin $admin): int
    {
        if ($this->option('super')) {
            $admin->is_super = true;
        }

        if ($name = $this->option('name')) {
            $admin->name = $name;
        }

        if ($password = $this->option('password')) {
            $admin->password = $password;
        }

        $admin->status = 'active';
        $admin->save();

        if ($this->option('permissions') !== null) {
            $admin->syncPermissions($this->requestedPermissions());
        }

        $this->info("Updated {$admin->email}.");
        $this->line('Access: ' . ($admin->is_super ? 'super admin (everything)' : $this->describe($admin)));

        return self::SUCCESS;
    }

    private function suspend(?Admin $admin, string $email): int
    {
        if (! $admin) {
            $this->error("No admin with the address {$email}.");

            return self::FAILURE;
        }

        $lastSuper = $admin->is_super
            && Admin::where('is_super', true)->where('status', 'active')->count() <= 1;

        if ($lastSuper) {
            $this->error('That is the only active super admin — promote someone else first.');

            return self::FAILURE;
        }

        $admin->update(['status' => 'suspended']);
        $this->info("{$admin->email} can no longer sign in.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function requestedPermissions(): array
    {
        $raw = (string) $this->option('permissions');

        if (trim($raw) === '') {
            return [];
        }

        $requested = array_filter(array_map('trim', explode(',', $raw)));
        $unknown   = array_diff($requested, array_keys(Admin::PERMISSIONS));

        foreach ($unknown as $permission) {
            $this->warn("Ignoring unknown permission: {$permission} (see --list)");
        }

        return $requested;
    }

    private function describe(Admin $admin): string
    {
        $granted = $admin->permissions->pluck('permission')->all();

        return $granted === [] ? 'sign-in only' : implode(', ', $granted);
    }
}
