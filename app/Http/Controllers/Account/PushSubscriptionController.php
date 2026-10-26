<?php

namespace App\Http\Controllers\Account;

use App\Enums\SiteFeature;
use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Support\Features;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Saves or removes this browser's web push subscription (sent by resources/js/ui/push.js).
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(Features::active(SiteFeature::WebPush), 404);

        $data = $request->validate([
            'endpoint' => ['required', 'url', 'starts_with:https://', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'in:aesgcm,aes128gcm'],
        ]);

        PushSubscription::query()->updateOrCreate(['endpoint' => $data['endpoint']], [
            'user_id' => $request->user()?->getKey(),
            'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
            'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
        ]);

        return response()->json(['ok' => true, 'message' => __('alerts.push.enabled')]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => ['required', 'string', 'max:500']]);

        PushSubscription::query()
            ->where('user_id', $request->user()?->getKey())
            ->where('endpoint', $request->string('endpoint')->toString())
            ->delete();

        return response()->json(['ok' => true, 'message' => __('alerts.push.disabled')]);
    }
}
