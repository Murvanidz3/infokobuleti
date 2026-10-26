<?php

namespace App\Livewire\Account;

use App\Enums\NotificationTopic;
use App\Models\User;
use App\Support\NotificationPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Notification center (database notifications) and the email preferences per topic.
 */
class Notifications extends Component
{
    use WithPagination;

    #[Url(except: false)]
    public bool $unread = false;

    /** @var array<string, bool> topic => wants email */
    public array $preferences = [];

    #[Locked]
    public string $path = '';

    public function mount(): void
    {
        $this->path = url()->current();

        foreach (NotificationTopic::cases() as $topic) {
            $this->preferences[$topic->value] = ($this->user()->notification_preferences[$topic->value] ?? true) !== false;
        }
    }

    public function updatedUnread(): void
    {
        $this->resetPage();
    }

    public function open(string $id): void
    {
        $notification = $this->find($id);
        $notification->markAsRead();

        $url = app(NotificationPresenter::class)->present($notification)['url'];

        if ($url !== null) {
            $this->redirect($url);
        }
    }

    public function markRead(string $id): void
    {
        $this->find($id)->markAsRead();
    }

    public function markAllRead(): void
    {
        $this->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->dispatch('toast', message: __('account.notifications.all_read'), type: 'success');
    }

    public function savePreferences(): void
    {
        $preferences = [];

        foreach (NotificationTopic::cases() as $topic) {
            $preferences[$topic->value] = (bool) ($this->preferences[$topic->value] ?? true);
        }

        $user = $this->user();
        $user->notification_preferences = $preferences;
        $user->save();

        $this->dispatch('toast', message: __('account.notifications.preferences_saved'), type: 'success');
    }

    public function render(NotificationPresenter $presenter): View
    {
        $user = $this->user();

        $paginator = $user->notifications()
            ->when($this->unread, fn ($query) => $query->whereNull('read_at'))
            ->paginate(20)
            ->withPath($this->path)
            ->appends(array_filter(['unread' => $this->unread ? 1 : null]));

        return view('livewire.account.notifications', [
            'items' => collect($paginator->items())->map(fn (DatabaseNotification $notification): array => $presenter->present($notification))->all(),
            'paginator' => $paginator,
            'unreadCount' => $user->unreadNotifications()->count(),
            'topics' => NotificationTopic::cases(),
            'hasEmail' => $user->email !== null,
        ]);
    }

    private function find(string $id): DatabaseNotification
    {
        /** @var DatabaseNotification */
        return $this->user()->notifications()->whereKey($id)->firstOrFail();
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
