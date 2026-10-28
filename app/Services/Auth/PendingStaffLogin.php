<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Session\Session;

/**
 * A staff member who passed the password step and still owes an authenticator code. Lives in the
 * session for ten minutes; nothing is signed in until the code is accepted.
 */
class PendingStaffLogin
{
    private const string KEY = 'auth.staff_pending';

    private const int TTL = 600;

    public function __construct(private Session $session) {}

    public function start(User $user, bool $remember): void
    {
        $this->session->put(self::KEY, ['id' => $user->id, 'remember' => $remember, 'at' => now()->getTimestamp()]);
    }

    public function user(): ?User
    {
        $pending = $this->session->get(self::KEY);

        if (! is_array($pending) || now()->getTimestamp() - $pending['at'] > self::TTL) {
            $this->clear();

            return null;
        }

        $user = User::query()->find($pending['id']);

        return $user !== null && $user->isStaff() && ! $user->is_blocked ? $user : null;
    }

    public function remember(): bool
    {
        return (bool) ($this->session->get(self::KEY)['remember'] ?? false);
    }

    /**
     * The not-yet-confirmed secret shown during first-time setup.
     */
    public function setupSecret(callable $generate): string
    {
        return $this->session->remember(self::KEY.'_secret', $generate);
    }

    public function clear(): void
    {
        $this->session->forget([self::KEY, self::KEY.'_secret']);
    }
}
