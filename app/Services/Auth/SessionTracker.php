<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Keeps user_sessions in step with the signed-in browsers, whatever the session driver, so users
 * can see their devices and sign the others out.
 */
class SessionTracker
{
    public const string SESSION_KEY = 'auth.device_id';

    /** Write activity at most once a minute per device. */
    private const int TOUCH_INTERVAL = 60;

    /**
     * Record a sign-in on this browser (also runs for "remember me" sign-ins).
     */
    public function start(Request $request, User $user, bool $remembered): UserSession
    {
        $session = $this->sessionStore();
        $deviceId = $session->get(self::SESSION_KEY);

        $row = $deviceId ? UserSession::query()->where('device_id', $deviceId)->where('user_id', $user->id)->first() : null;

        if ($row === null) {
            $deviceId = (string) Str::ulid();
            $session->put(self::SESSION_KEY, $deviceId);
            $row = new UserSession(['user_id' => $user->id, 'device_id' => $deviceId]);
        }

        $row->fill([
            'session_id' => $session->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'remembered' => $remembered || $row->remembered,
            'last_active_at' => now(),
        ])->save();

        return $row;
    }

    /**
     * False when this browser was signed out from another device.
     */
    public function isRevoked(Request $request): bool
    {
        $deviceId = $this->sessionStore()->get(self::SESSION_KEY);

        return $deviceId !== null && ! UserSession::query()->where('device_id', $deviceId)->exists();
    }

    /**
     * Refresh the session id, IP and last activity after a request.
     */
    public function touch(Request $request, User $user): void
    {
        $session = $this->sessionStore();
        $deviceId = $session->get(self::SESSION_KEY);

        if ($deviceId === null) {
            $this->start($request, $user, Auth::viaRemember());

            return;
        }

        UserSession::query()
            ->where('device_id', $deviceId)
            ->where(fn ($query) => $query
                ->where('session_id', '!=', $session->getId())
                ->orWhere('last_active_at', '<', now()->subSeconds(self::TOUCH_INTERVAL)))
            ->update([
                'session_id' => $session->getId(),
                'ip_address' => $request->ip(),
                'last_active_at' => now(),
            ]);
    }

    public function currentDeviceId(Request $request): ?string
    {
        return $this->sessionStore()->get(self::SESSION_KEY);
    }

    /**
     * Sign out one other device.
     */
    public function revoke(User $user, int $userSessionId, Request $request): bool
    {
        $row = $user->userSessions()->whereKey($userSessionId)->first();

        if ($row === null || $row->device_id === $this->currentDeviceId($request)) {
            return false;
        }

        $this->destroy($row);

        return true;
    }

    /**
     * Sign out every device except this one, including "remember me" cookies.
     */
    public function revokeOthers(User $user, Request $request): int
    {
        $current = $this->currentDeviceId($request);
        $rows = $user->userSessions()->where('device_id', '!=', (string) $current)->get();

        $rows->each(fn (UserSession $row) => $this->destroy($row));

        // A new remember token invalidates the cookies of other devices. This browser keeps its
        // own by signing in again with the new token (same device row, so nothing is duplicated).
        $user->setRememberToken(Str::random(60));
        $user->save();

        $currentRow = $current ? $user->userSessions()->where('device_id', $current)->first() : null;
        Auth::guard('web')->login($user, (bool) $currentRow?->remembered);

        return $rows->count();
    }

    /**
     * Forget this browser on sign-out.
     */
    public function end(Request $request): void
    {
        $deviceId = $this->sessionStore()->get(self::SESSION_KEY);

        if ($deviceId !== null) {
            UserSession::query()->where('device_id', $deviceId)->delete();
        }
    }

    private function destroy(UserSession $row): void
    {
        $this->sessionStore()->getHandler()->destroy($row->session_id);
        $row->delete();
    }

    private function sessionStore(): Session
    {
        return app('session')->driver();
    }
}
