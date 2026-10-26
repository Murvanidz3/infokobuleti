<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * One line per failed sign-in step in storage/logs/auth.log ("auth-failed staff-password
 * ip=1.2.3.4"). The server's fail2ban jail bans addresses that fail too often.
 */
final class AuthLog
{
    public static function failed(Request $request, string $step): void
    {
        Log::channel('auth')->warning(sprintf('auth-failed %s ip=%s', $step, $request->ip()));
    }
}
