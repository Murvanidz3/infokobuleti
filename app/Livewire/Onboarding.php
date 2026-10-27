<?php

namespace App\Livewire;

use App\Actions\Auth\CompleteOnboarding;
use App\Enums\AccountType;
use App\Enums\BusinessType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * First sign-in (also the end of sign-up by phone): first and last name, email when the account
 * has none yet, account type, plus the business profile for business accounts.
 */
class Onboarding extends Component
{
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $accountType = 'individual';

    public string $businessType = '';

    public string $businessName = '';

    public function mount(): void
    {
        [$first, $last] = array_pad(explode(' ', trim((string) $this->user()->name), 2), 2, '');
        $this->firstName = $first;
        $this->lastName = trim($last);
    }

    /** Phone sign-ups have no email yet; Google accounts already do. */
    public function needsEmail(): bool
    {
        return blank($this->user()->email);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'min:2', 'max:40'],
            'lastName' => ['required', 'string', 'min:2', 'max:40'],
            'email' => $this->needsEmail()
                ? ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($this->user()->getKey())->whereNull('deleted_at')]
                : ['exclude'],
            'accountType' => ['required', Rule::enum(AccountType::class)],
            'businessType' => ['exclude_unless:accountType,business', 'required', Rule::enum(BusinessType::class)],
            'businessName' => ['exclude_unless:accountType,business', 'required', 'string', 'min:2', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'firstName' => __('auth.onboarding.first_name'),
            'lastName' => __('auth.onboarding.last_name'),
            'email' => __('auth.onboarding.email'),
            'accountType' => __('auth.onboarding.account_type'),
            'businessType' => __('auth.onboarding.business_type'),
            'businessName' => __('auth.onboarding.business_name'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return ['email.unique' => __('auth.onboarding.email_taken')];
    }

    public function save(CompleteOnboarding $onboarding): void
    {
        $this->email = mb_strtolower(trim($this->email));
        $data = $this->validate();
        $accountType = AccountType::from($data['accountType']);

        $onboarding->handle(
            $this->user(),
            trim($data['firstName']).' '.trim($data['lastName']),
            $accountType,
            $accountType === AccountType::Business ? BusinessType::from($data['businessType']) : null,
            $accountType === AccountType::Business ? trim($data['businessName']) : null,
            isset($data['email']) ? mb_strtolower(trim($data['email'])) : null,
        );

        $user = $this->user();
        $target = session()->pull('url.intended', localized_route('home'));

        // Google accounts are asked for a phone now; they can skip it until they post something.
        if (! $user->hasVerifiedPhone()) {
            session()->put('url.intended', $target);
            $target = localized_route('account.phone');
        }

        session()->flash('status', __('auth.onboarding.done'));
        $this->redirect($target);
    }

    public function render(): View
    {
        return view('livewire.onboarding', [
            'needsEmail' => $this->needsEmail(),
            'accountTypes' => AccountType::options(),
            'businessTypes' => BusinessType::options(),
        ]);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
