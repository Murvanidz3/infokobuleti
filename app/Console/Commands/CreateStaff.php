<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('staff:create {email} {name} {--role=super_admin : super_admin, admin or moderator}')]
#[Description('Create a staff account (or give an existing account a staff role) and print a one-time password')]
class CreateStaff extends Command
{
    public function handle(): int
    {
        $role = Role::tryFrom((string) $this->option('role'));
        $email = mb_strtolower(trim((string) $this->argument('email')));

        if ($role === null || ! $role->isStaff()) {
            $this->error('Unknown staff role.');

            return self::FAILURE;
        }

        if (Validator::make(['email' => $email], ['email' => ['required', 'email:rfc']])->fails()) {
            $this->error('Invalid email.');

            return self::FAILURE;
        }

        $password = Str::password(20, symbols: false);
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $user->name ?: (string) $this->argument('name'),
            'password' => Hash::make($password),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
        $user->syncRoles([$role->value]);

        activity('admin')->performedOn($user)->event('staff_created')->withProperties(['role' => $role->value])->log('staff account created from the console');

        $this->info("Staff account ready: {$email} ({$role->value})");
        $this->line("One-time password: {$password}");
        $this->line('Sign in at '.localized_route('staff.login').' and set up two-factor authentication; change nothing else here.');

        return self::SUCCESS;
    }
}
