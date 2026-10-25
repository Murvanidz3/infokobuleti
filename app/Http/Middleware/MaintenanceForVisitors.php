<?php

namespace App\Http\Middleware;

use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maintenance mode switched on from the admin: visitors get a 503 page, staff keep working and
 * sign-in, the admin and webhooks stay reachable.
 */
class MaintenanceForVisitors
{
    private const array ALWAYS_OPEN = ['admin', 'admin/*', 'livewire/*', '*staff/*', 'logout', 'webhooks/*', 'up'];

    public function __construct(private Settings $settings) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->maintenanceMode() || $request->is(...self::ALWAYS_OPEN) || $request->user()?->isStaff()) {
            return $next($request);
        }

        $message = $this->settings->get('site.maintenance_message.'.app()->getLocale())
            ?? $this->settings->get('site.maintenance_message.ka');

        return response()->view('pages.maintenance', ['message' => $message], 503)->header('Retry-After', '600');
    }
}
