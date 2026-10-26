<?php

namespace App\Support;

use App\Services\DateFormatter;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Turns stored (database) notifications into rows for the notification center. Texts are built at
 * display time, so they follow the visitor's current language.
 */
class NotificationPresenter
{
    public function __construct(private DateFormatter $dates) {}

    /**
     * @return array{id: string, icon: string, title: string, text: ?string, url: ?string, time: string, unread: bool, alert: bool}
     */
    public function present(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;
        $type = (string) ($data['type'] ?? '');
        $title = (string) ($data['title'] ?? '');

        [$icon, $heading, $text, $url, $alert] = match ($type) {
            'listing_moderated' => ($data['approved'] ?? false)
                ? ['check-circle', __('account.notifications.types.approved', ['title' => $title]), null, localized_route('account.listings'), false]
                : ['alert', __('account.notifications.types.rejected', ['title' => $title]), $data['reason'] ?? null, localized_route('account.listings', ['group' => 'attention']), true],
            'listing_expiring' => ['calendar', __('account.notifications.types.expiring', ['title' => $title]), __('account.notifications.types.expiring_text', ['date' => (string) ($data['date'] ?? '')]), localized_route('account.listings', ['group' => 'active']), true],
            'listing_expired' => ['calendar', __('account.notifications.types.expired', ['title' => $title]), __('account.notifications.types.expired_text'), localized_route('account.listings', ['group' => 'expired']), true],
            'inquiry_message' => ['message', __('account.notifications.types.message', ['title' => $title]), $this->excerpt($data['details']['text'] ?? null), $data['url'] ?? localized_route('account.messages'), false],
            'inquiry_booking' => ['calendar', __('account.notifications.types.booking', ['title' => $title]), $this->details($data), $data['url'] ?? localized_route('account.bookings'), false],
            'inquiry_application' => ['briefcase', __('account.notifications.types.application', ['title' => $title]), $this->details($data), $data['url'] ?? localized_route('account.applications'), false],
            'booking_answered' => ($data['accepted'] ?? false)
                ? ['check-circle', __('account.notifications.types.booking_accepted', ['title' => $title]), $data['note'] ?? null, $data['url'] ?? null, false]
                : ['info', __('account.notifications.types.booking_declined', ['title' => $title]), $data['note'] ?? null, $data['url'] ?? null, false],
            'application_status' => ['briefcase', __('account.notifications.types.application_status', ['title' => $title, 'status' => __('enums.application_status.'.($data['status'] ?? 'viewed'))]), null, $data['url'] ?? null, false],
            'saved_search_alert' => ['search', trans_choice('alerts.center', (int) ($data['count'] ?? 1), ['count' => (int) ($data['count'] ?? 1), 'name' => $title]), null, $data['url'] ?? localized_route('account.searches'), false],
            'payment_receipt' => ['check-circle', __('billing.notifications.receipt', ['number' => (string) ($data['number'] ?? '')]), $this->orderText($data), localized_route('account.billing'), false],
            'order_refunded' => ['wallet', __('billing.notifications.refunded', ['amount' => (string) ($data['amount'] ?? '')]), ($data['to_balance'] ?? false) ? __('billing.notifications.refunded_balance') : __('billing.notifications.refunded_card'), localized_route('account.billing'), false],
            'invoice_issued' => ['file', __('billing.notifications.invoice', ['amount' => (string) ($data['amount'] ?? '')]), $this->translated($data['title'] ?? null), localized_route('account.billing'), true],
            'promotion_expiring' => ['star', __('billing.notifications.expiring', ['kind' => __('billing.expiring.kinds.'.($data['kind'] ?? 'vip'))]), $title !== '' ? __('billing.notifications.expiring_text', ['title' => $title, 'date' => (string) ($data['date'] ?? '')]) : __('billing.notifications.expiring_package', ['date' => (string) ($data['date'] ?? '')]), isset($data['listing_type'], $data['listing_id']) ? localized_route('account.promote', ['type' => $data['listing_type'], 'id' => $data['listing_id']]) : localized_route('account.billing'), true],
            default => ['info', $title !== '' ? $title : __('account.notifications.types.generic'), null, null, false],
        };

        return [
            'id' => (string) $notification->getKey(),
            'icon' => $icon,
            'title' => $heading,
            'text' => $text,
            'url' => $url,
            'time' => $this->dates->relative($notification->created_at ?? now()),
            'unread' => $notification->read_at === null,
            'alert' => $alert,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function details(array $data): ?string
    {
        $details = is_array($data['details'] ?? null) ? $data['details'] : [];

        return collect([$details['name'] ?? null, $details['dates'] ?? null, $this->excerpt($details['text'] ?? null)])->filter()->implode(' · ') ?: null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function orderText(array $data): ?string
    {
        $title = $this->translated($data['title'] ?? null);

        if ($title === null && is_string($data['product'] ?? null)) {
            $title = __('enums.price_plan_product.'.$data['product']);
        }

        return collect([$title, $data['amount'] ?? null])->filter()->implode(' · ') ?: null;
    }

    private function translated(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[app()->getLocale()] ?? $value['ka'] ?? reset($value);
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function excerpt(mixed $text): ?string
    {
        $text = trim((string) $text);

        return $text === '' ? null : mb_strimwidth($text, 0, 140, '…');
    }
}
