<?php

namespace App\Livewire\Account;

use App\Models\User;
use App\Services\LocaleRouter;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Name, email and preferred language (emails and SMS are sent in that language).
 */
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $locale = 'ka';

    public function mount(): void
    {
        $user = $this->user();
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->locale = $user->preferredLocale();
    }

    public function save(): void
    {
        $user = $this->user();

        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['nullable', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->getKey())],
            'locale' => ['required', Rule::in(array_keys(app(LocaleRouter::class)->supported()))],
        ], [], [
            'name' => __('auth.onboarding.name'),
            'email' => __('account.profile.email'),
            'locale' => __('account.profile.locale'),
        ]);

        $email = filled($data['email']) ? mb_strtolower(trim($data['email'])) : null;

        // Staff sign in with their email: it cannot be removed here.
        if ($email === null && $user->isStaff()) {
            $this->addError('email', __('account.profile.email_required_staff'));

            return;
        }

        if ($email !== $user->email) {
            $user->email_verified_at = null;
        }

        $user->forceFill(['name' => trim($data['name']), 'email' => $email, 'locale' => $data['locale']])->save();

        $this->dispatch('toast', message: __('account.profile.saved'), type: 'success');
    }

    public function render(): View
    {
        return view('livewire.account.profile', [
            'locales' => collect(app(LocaleRouter::class)->supported())->map(fn (array $locale, string $code): string => __('locales.'.$code))->all(),
        ]);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
