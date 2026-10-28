<?php

namespace App\Livewire\Account;

use App\Actions\Account\SaveBusinessProfile;
use App\Enums\AccountType;
use App\Enums\BusinessType;
use App\Models\BusinessProfile;
use App\Models\Location;
use App\Models\User;
use App\Support\GeorgianPhone;
use App\Support\Listings\Contact;
use App\Support\Listings\PublicUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The business card shown on listings and on the public business page. Individuals can create
 * one here, which turns their account into a business account.
 */
class BusinessProfileForm extends Component
{
    use WithFileUploads;

    public bool $open = false;

    public string $type = '';

    public string $name = '';

    /** @var array<string, string> */
    public array $description = ['ka' => '', 'en' => '', 'ru' => ''];

    public string $phone = '';

    public string $email = '';

    public string $website = '';

    public string $address = '';

    public string $locationId = '';

    /** @var TemporaryUploadedFile|null */
    public $logo = null;

    public function mount(): void
    {
        $profile = $this->profile();
        $this->open = $profile !== null;

        if ($profile === null) {
            return;
        }

        $this->type = $profile->type->value;
        $this->name = $profile->name;
        $this->description = array_merge($this->description, array_map('strval', $profile->getTranslations('description')));
        $this->phone = $profile->phone !== null ? GeorgianPhone::format($profile->phone) : '';
        $this->email = (string) $profile->email;
        $this->website = (string) $profile->website;
        $this->address = (string) $profile->getTranslation('address', 'ka', false);
        $this->locationId = $profile->location_id !== null ? (string) $profile->location_id : '';
    }

    public function save(SaveBusinessProfile $action): void
    {
        $data = $this->validate([
            'type' => ['required', Rule::enum(BusinessType::class)],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description.ka' => ['nullable', 'string', 'max:3000'],
            'description.en' => ['nullable', 'string', 'max:3000'],
            'description.ru' => ['nullable', 'string', 'max:3000'],
            'phone' => ['nullable', 'string', 'max:24', 'regex:/^[\d\s()+-]{6,24}$/'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'website' => ['nullable', 'url:http,https', 'max:190'],
            'address' => ['nullable', 'string', 'max:190'],
            'locationId' => ['nullable', Rule::exists('locations', 'id')],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'type' => __('auth.onboarding.business_type'),
            'name' => __('auth.onboarding.business_name'),
            'phone' => __('account.business.phone'),
            'email' => __('account.profile.email'),
            'website' => __('account.business.website'),
            'address' => __('account.business.address'),
            'locationId' => __('account.business.location'),
            'logo' => __('account.business.logo'),
        ]);

        $action->handle($this->user(), $this->profile(), [
            'type' => $data['type'],
            'name' => trim($data['name']),
            'description' => $data['description'] ?? [],
            'phone' => filled($data['phone']) ? Contact::normalizePhone($data['phone']) : null,
            'email' => filled($data['email']) ? mb_strtolower(trim($data['email'])) : null,
            'website' => filled($data['website']) ? trim($data['website']) : null,
            'address' => filled($data['address']) ? trim($data['address']) : null,
            'location_id' => filled($data['locationId']) ? (int) $data['locationId'] : null,
        ], $this->logo instanceof UploadedFile ? $this->logo : null);

        $this->logo = null;
        $this->dispatch('toast', message: __('account.business.saved'), type: 'success');
    }

    public function render(): View
    {
        $profile = $this->profile();

        return view('livewire.account.business-profile-form', [
            'profile' => $profile,
            'isBusiness' => $this->user()->account_type === AccountType::Business,
            'publicUrl' => $profile !== null ? PublicUrl::for($profile) : null,
            'logoUrl' => $profile?->getFirstMediaUrl('logo') ?: null,
            'typeOptions' => BusinessType::options(),
            'locationOptions' => Location::query()->active()->whereNotNull('parent_id')->orderBy('sort')->get()->mapWithKeys(fn (Location $location): array => [(string) $location->id => (string) $location->name])->all(),
        ]);
    }

    private function profile(): ?BusinessProfile
    {
        return $this->user()->businessProfiles()->with('media')->oldest('id')->first();
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
