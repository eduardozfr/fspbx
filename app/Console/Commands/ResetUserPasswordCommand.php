<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:reset-password
        {email : Login email address of the user}
        {--password= : Plain-text password to set. If omitted, a strong password will be generated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset a user password by email and print the new password once.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $users = User::query()
            ->where('user_email', $email)
            ->get(['user_uuid', 'username', 'user_email', 'domain_uuid']);

        if ($users->isEmpty()) {
            $this->error("No user was found with e-mail {$email}.");

            return self::FAILURE;
        }

        if ($users->count() > 1) {
            $this->error("More than one user matched {$email}. Refine the account manually before resetting.");
            $this->table(
                ['user_uuid', 'username', 'user_email', 'domain_uuid'],
                $users->map(fn (User $user) => [
                    'user_uuid' => $user->user_uuid,
                    'username' => $user->username,
                    'user_email' => $user->user_email,
                    'domain_uuid' => $user->domain_uuid,
                ])->all()
            );

            return self::FAILURE;
        }

        $user = $users->first();
        $plainPassword = trim((string) $this->option('password'));

        if ($plainPassword === '') {
            $plainPassword = function_exists('generate_password')
                ? generate_password()
                : bin2hex(random_bytes(12));
        }

        $user->password = Hash::make($plainPassword);
        $user->save();

        $this->info("Password updated successfully for {$user->user_email}.");
        $this->line("Username: {$user->username}");
        $this->line("Temporary password: {$plainPassword}");
        $this->warn('Store this password now. It will not be displayed again.');

        return self::SUCCESS;
    }
}
