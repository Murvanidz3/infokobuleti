<?php

namespace App\Livewire\Account;

use App\Actions\Privacy\DeleteAccount;
use App\Jobs\ExportUserDataJob;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/**
 * "Your data": download everything the site keeps about you, or delete the account.
 */
class Privacy extends Component
{
    public string $confirmation = '';

    public bool $confirming = false;

    public function requestExport(): void
    {
        $key = 'data-export:'.$this->user()->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $this->dispatch('toast', message: __('privacy.export.wait'), type: 'error');

            return;
        }

        RateLimiter::hit($key, 3600);
        ExportUserDataJob::dispatch($this->user()->id);

        $this->dispatch('toast', message: __('privacy.export.started'), type: 'success');
    }

    public function deleteAccount(DeleteAccount $delete): mixed
    {
        $user = $this->user();

        if ($user->isStaff()) {
            $this->dispatch('toast', message: __('privacy.delete.staff'), type: 'error');

            return null;
        }

        $this->validate(
            ['confirmation' => ['required', 'string', 'in:'.__('privacy.delete.word')]],
            ['confirmation.in' => __('privacy.delete.mismatch', ['word' => __('privacy.delete.word')])],
            ['confirmation' => __('privacy.delete.confirm_label', ['word' => __('privacy.delete.word')])],
        );

        $delete->handle($user);

        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('toast', ['message' => __('privacy.delete.done'), 'type' => 'success']);

        return $this->redirect(localized_route('home'));
    }

    public function render(): View
    {
        return view('livewire.account.privacy', [
            'staff' => $this->user()->isStaff(),
            'word' => __('privacy.delete.word'),
        ]);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
