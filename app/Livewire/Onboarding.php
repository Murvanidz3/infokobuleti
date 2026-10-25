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
 * First sign-in: name and account type, plus the business profile for business accounts.
 */
class Onboarding extends Component
{
    public string $name = '';

    public string $accountType = 'individual';

    public string $businessType = '';

    public string $businessName = '';

    public function mount(): void
    {
        $this->name = (string) $this->user()->name;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
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
            'name' => __('auth.onboarding.name'),
            'accountType' => __('auth.onboarding.account_type'),
            'businessType' => __('auth.onboarding.business_type'),
            'businessName' => __('auth.onboarding.business_name'),
        ];
    }

    public function save(CompleteOnboarding $onboarding): void
    {
        $data = $this->validate();
        $accountType = AccountType::from($data['accountType']);

        $onboarding->handle(
            $this->user(),
            trim($data['name']),
            $accountType,
            $accountType === AccountType::Business ? BusinessType::from($data['businessType']) : null,
            $accountType === AccountType::Business ? trim($data['businessName']) : null,
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
