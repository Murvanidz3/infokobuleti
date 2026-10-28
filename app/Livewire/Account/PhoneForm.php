<?php

namespace App\Livewire\Account;

use App\Actions\Auth\ChangePhone;
use App\Enums\OtpPurpose;
use App\Exceptions\AuthFlowException;
use App\Exceptions\OtpException;
use App\Models\User;
use App\Rules\GeorgianMobile;
use App\Services\Auth\PhoneOtp;
use App\Support\GeorgianPhone;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Add or change the account phone: the new number is saved only after its SMS code is entered.
 */
class PhoneForm extends Component
{
    /** "settings" (inside account security) or "verify" (the add-a-phone page). */
    #[Locked]
    public string $context = 'settings';

    public string $phone = '';

    public string $code = '';

    public string $step = 'form';

    #[Locked]
    public ?string $pendingPhone = null;

    public int $resendIn = 0;

    public ?string $devCode = null;

    public function sendCode(PhoneOtp $otp): void
    {
        $this->resetErrorBag();
        $this->validate(['phone' => ['required', 'string', 'max:32', new GeorgianMobile]], attributes: ['phone' => __('auth.phone.label')]);

        $phone = (string) GeorgianPhone::normalize($this->phone);

        if ($phone === $this->user()->phone && $this->user()->hasVerifiedPhone()) {
            $this->addError('phone', __('auth.phone.same'));

            return;
        }

        if (User::query()->where('phone', $phone)->whereKeyNot($this->user()->id)->exists()) {
            $this->addError('phone', __('auth.phone.taken'));

            return;
        }

        $this->deliver($otp, $phone);
    }

    public function resend(PhoneOtp $otp): void
    {
        if ($this->pendingPhone !== null) {
            $this->resetErrorBag();
            $this->deliver($otp, $this->pendingPhone);
        }
    }

    public function confirm(PhoneOtp $otp, ChangePhone $changePhone): void
    {
        $this->resetErrorBag();

        if ($this->pendingPhone === null) {
            $this->step = 'form';

            return;
        }

        try {
            $otp->verify($this->pendingPhone, OtpPurpose::ChangePhone, $this->code, request()->ip());
            $changePhone->handle($this->user(), $this->pendingPhone);
        } catch (OtpException $exception) {
            $this->addError('code', $exception->userMessage());

            if (in_array($exception->error->value, ['expired', 'too_many_attempts'], true)) {
                $this->code = '';
            }

            return;
        } catch (AuthFlowException $exception) {
            $this->addError('code', $exception->userMessage());

            return;
        }

        if ($this->context === 'verify') {
            session()->flash('status', __('auth.phone.saved'));
            $this->redirect(session()->pull('url.intended', localized_route('account.security')));

            return;
        }

        $this->reset(['phone', 'code', 'step', 'pendingPhone', 'resendIn', 'devCode']);
        $this->dispatch('phone-updated');
        $this->dispatch('toast', message: __('auth.phone.saved'), type: 'success');
    }

    public function cancel(): void
    {
        $this->reset(['phone', 'code', 'step', 'pendingPhone', 'resendIn', 'devCode']);
        $this->resetErrorBag();
        $this->dispatch('phone-form-closed');
    }

    public function render(): View
    {
        return view('livewire.account.phone-form', [
            'pendingFormatted' => GeorgianPhone::format($this->pendingPhone),
        ]);
    }

    private function deliver(PhoneOtp $otp, string $phone): void
    {
        try {
            $sent = $otp->send($phone, OtpPurpose::ChangePhone, request()->ip(), $this->user());
        } catch (OtpException $exception) {
            $this->addError($this->step === 'code' ? 'code' : 'phone', $exception->userMessage());
            $this->resendIn = $exception->retryAfter;

            return;
        }

        $this->pendingPhone = $phone;
        $this->step = 'code';
        $this->code = '';
        $this->resendIn = $sent->resendAfter;
        $this->devCode = $sent->revealedCode;
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
