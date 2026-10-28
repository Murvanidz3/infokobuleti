<?php

namespace App\Jobs;

use App\Actions\Privacy\ExportUserData;
use App\Models\User;
use App\Notifications\DataExportReady;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Builds the "download my data" file in the background and sends the user the link.
 */
class ExportUserDataJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId) {}

    public function handle(ExportUserData $export): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;
        }

        $user->notify(new DataExportReady(basename($export->handle($user))));
    }
}
