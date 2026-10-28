<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

#[Signature('webpush:keys {--show : Print the keys instead of writing them to .env}')]
#[Description('Create the VAPID key pair for web push and add it to .env (only when it is missing)')]
class GenerateVapidKeys extends Command
{
    public function handle(): int
    {
        if (filled(config('webpush.vapid.public_key')) && ! $this->option('show')) {
            $this->info('VAPID keys already exist; keeping them.');

            return self::SUCCESS;
        }

        $keys = VAPID::createVapidKeys();

        if ($this->option('show')) {
            $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);

            return self::SUCCESS;
        }

        file_put_contents(base_path('.env'), "\nVAPID_PUBLIC_KEY={$keys['publicKey']}\nVAPID_PRIVATE_KEY={$keys['privateKey']}\n", FILE_APPEND);
        $this->info('VAPID keys written to .env.');

        return self::SUCCESS;
    }
}
