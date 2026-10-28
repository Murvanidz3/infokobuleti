<?php

namespace App\Livewire\Account;

use App\Actions\Vacancies\ChangeApplicationStatus;
use App\Enums\ApplicationStatus;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use App\Services\DateFormatter;
use App\Support\GeorgianPhone;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Employers: applications per vacancy with a status pipeline (new → viewed → shortlisted →
 * hired / rejected) and CV downloads.
 */
class Applications extends Component
{
    use WithPagination;

    #[Url(except: 0)]
    public int $vacancy = 0;

    #[Url(except: '')]
    public string $status = '';

    /** Application whose details are expanded. */
    public ?int $open = null;

    #[Locked]
    public string $path = '';

    public function mount(): void
    {
        $this->path = url()->current();

        if (ApplicationStatus::tryFrom($this->status) === null) {
            $this->status = '';
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['vacancy', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function toggle(ChangeApplicationStatus $action, int $id): void
    {
        $application = $this->find($id);
        $this->open = $this->open === $id ? null : $id;

        if ($this->open === $id && $application->status === ApplicationStatus::New) {
            $action->handle($application, ApplicationStatus::Viewed);
        }
    }

    public function setStatus(ChangeApplicationStatus $action, int $id, string $status): void
    {
        $value = ApplicationStatus::tryFrom($status);

        if ($value === null) {
            return;
        }

        $action->handle($this->find($id), $value);
        $this->dispatch('toast', message: __('account.applications.moved', ['status' => $value->label()]), type: 'success');
    }

    public function render(DateFormatter $dates): View
    {
        $vacancies = Vacancy::query()->where('user_id', $this->user()->id)->withCount('applications')->latest()->get();

        $base = VacancyApplication::query()
            ->whereIn('vacancy_id', $vacancies->modelKeys())
            ->when($this->vacancy > 0, fn ($query) => $query->where('vacancy_id', $this->vacancy));

        $counts = (clone $base)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($count): int => (int) $count)->all();

        $paginator = (clone $base)
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->with(['user', 'vacancy', 'media'])
            ->latest()
            ->paginate(20)
            ->withPath($this->path)
            ->appends(array_filter(['vacancy' => $this->vacancy ?: null, 'status' => $this->status ?: null]));

        return view('livewire.account.applications', [
            'vacancies' => $vacancies,
            'counts' => $counts,
            'total' => array_sum($counts),
            'paginator' => $paginator,
            'statuses' => ApplicationStatus::options(),
            'applications' => collect($paginator->items())->map(fn (VacancyApplication $application): array => [
                'id' => $application->id,
                'name' => $application->user->name ?? __('account.messages.deleted_user'),
                'vacancy' => (string) $application->vacancy?->title,
                'phone' => $application->contact_phone !== null ? GeorgianPhone::format($application->contact_phone) : null,
                'tel' => $application->contact_phone !== null ? 'tel:'.$application->contact_phone : null,
                'email' => $application->user?->email,
                'note' => $application->cover_note,
                'cv' => $application->getFirstMedia('cv') !== null ? localized_route('account.applications.cv', ['application' => $application->id]) : null,
                'status' => $application->status,
                'created' => $dates->relative($application->created_at ?? now()),
            ])->all(),
        ]);
    }

    private function find(int $id): VacancyApplication
    {
        return VacancyApplication::query()
            ->whereHas('vacancy', fn ($query) => $query->where('user_id', $this->user()->id))
            ->findOrFail($id);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
