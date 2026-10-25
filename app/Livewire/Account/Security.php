<?php

namespace App\Livewire\Account;

use App\Actions\Auth\ChangePhone;
use App\Actions\Auth\UnlinkGoogleAccount;
use App\Exceptions\AuthFlowException;
use App\Models\User;
use App\Services\Auth\SessionTracker;
use App\Services\Auth\TwoFactorAuthenticator;
use App\Support\GeorgianPhone;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Sign-in methods (Google, phone, staff password + 2FA) and signed-in devices.
 */
class Security extends Component
{
    public bool $editingPhone = false;

    /** @var list<string> */
    public array $recoveryCodes = [];

    #[On('phone-updated')]
    #[On('phone-form-closed')]
    public function closePhoneForm(): void
    {
        $this->editingPhone = false;
    }

    public function unlinkGoogle(UnlinkGoogleAccount $unlink): void
    {
        $this->attempt(fn () => $unlink->handle($this->user()), __('auth.google.unlinked'));
    }

    public function removePhone(ChangePhone $changePhone): void
    {
        $this->attempt(fn () => $changePhone->remove($this->user()), __('auth.phone.removed'));
    }

    public function revokeSession(int $id, SessionTracker $tracker): void
    {
        if ($tracker->revoke($this->user(), $id, request())) {
            $this->dispatch('toast', message: __('auth.sessions.revoked'), type: 'success');
        }
    }

    public function logoutOtherDevices(SessionTracker $tracker): void
    {
        $count = $tracker->revokeOthers($this->user(), request());

        $this->dispatch('toast', message: trans_choice('auth.sessions.revoked_others', $count, ['count' => $count]), type: 'success');
    }

    public function regenerateRecoveryCodes(TwoFactorAuthenticator $twoFactor): void
    {
        abort_unless($this->user()->isStaff() && $this->user()->hasTwoFactorEnabled(), 403);

        $this->recoveryCodes = $twoFactor->regenerateRecoveryCodes($this->user());
    }

    public function render(SessionTracker $tracker): View
    {
        $user = $this->user();

        // Make sure this browser is listed even on the first page view after an upgrade.
        $tracker->touch(request(), $user);

        return view('livewire.account.security', [
            'user' => $user,
            'phone' => GeorgianPhone::format($user->phone),
            'sessions' => $user->userSessions()->orderByDesc('last_active_at')->get(),
            'currentDevice' => $tracker->currentDeviceId(request()),
            'recoveryCodesLeft' => count($user->two_factor_recovery_codes ?? []),
        ]);
    }

    /**
     * @param  callable(): void  $action
     */
    private function attempt(callable $action, string $success): void
    {
        try {
            $action();
        } catch (AuthFlowException $exception) {
            $this->dispatch('toast', message: $exception->userMessage(), type: 'error');

            return;
        }

        $this->dispatch('toast', message: $success, type: 'success');
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user()->fresh();
    }
}
