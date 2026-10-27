<?php

namespace App\Filament\Resources\SmsCampaigns\Pages;

use App\Enums\SmsCampaignStatus;
use App\Enums\SmsStatus;
use App\Filament\Resources\SmsCampaigns\SmsCampaignResource;
use App\Filament\Support\SmsFields;
use App\Models\SmsCampaign;
use App\Models\User;
use App\Services\Sms\Campaigns;
use App\Services\Sms\DeliveryReports;
use App\Services\Sms\ProviderStatus;
use App\Support\GeorgianPhone;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Review and send: the recipient check, cost and balance, then send now, schedule, test, cancel;
 * after sending the delivery figures, report refresh, CSV export and a retry for failed numbers.
 */
class ViewSmsCampaign extends ViewRecord
{
    protected static string $resource = SmsCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label(__('sms.actions.send_now'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn (): bool => $this->campaign()->status->isEditable() && $this->campaign()->recipients_count > 0)
                ->requiresConfirmation()
                ->modalHeading(__('sms.campaigns.confirm_heading'))
                ->modalDescription(fn (ProviderStatus $status): string => __('sms.campaigns.confirm_text', [
                    'recipients' => $this->campaign()->recipients_count,
                    'total' => $this->campaign()->estimatedSms(),
                    'balance' => $status->balance() ?? '—',
                ]))
                ->modalSubmitActionLabel(__('sms.actions.send_now'))
                ->action(fn (Campaigns $campaigns) => $this->run(fn () => $campaigns->start($this->campaign()), __('sms.notifications.campaign_started'))),
            Action::make('schedule')
                ->label(__('sms.actions.schedule'))
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->visible(fn (): bool => $this->campaign()->status->isEditable() && $this->campaign()->recipients_count > 0)
                ->fillForm(fn (): array => ['scheduled_at' => $this->campaign()->scheduled_at ?? now()->addHour()->startOfHour()])
                ->schema([
                    DateTimePicker::make('scheduled_at')->label(__('sms.fields.scheduled_at'))->seconds(false)->required()
                        ->minDate(fn (): Carbon => now())->helperText(__('sms.campaigns.schedule_help')),
                ])
                ->action(fn (array $data, Campaigns $campaigns) => $this->run(
                    fn () => Carbon::parse($data['scheduled_at'])->isFuture()
                        ? $campaigns->start($this->campaign(), Carbon::parse($data['scheduled_at']))
                        : throw new RuntimeException(__('sms.errors.past_time')),
                    __('sms.notifications.campaign_scheduled'),
                )),
            Action::make('test')
                ->label(__('sms.actions.test'))
                ->icon(Heroicon::OutlinedBeaker)
                ->color('gray')
                ->visible(fn (): bool => $this->campaign()->status->isEditable())
                ->fillForm(fn (): array => ['phone' => SmsFields::testPhone()])
                ->schema([SmsFields::phone()->helperText(__('sms.campaigns.test_help'))])
                ->action(function (array $data, Campaigns $campaigns): void {
                    /** @var User $staff */
                    $staff = auth()->user();
                    $message = $campaigns->sendTest($this->campaign(), (string) GeorgianPhone::normalize((string) $data['phone']), $staff);

                    $message->status === SmsStatus::Failed
                        ? Notification::make()->danger()->title(__('sms.notifications.failed'))->body($message->error)->send()
                        : Notification::make()->success()->title(__('sms.notifications.queued', ['phone' => GeorgianPhone::format($message->phone)]))->send();
                }),
            EditAction::make()->color('gray')->visible(fn (): bool => $this->campaign()->status->isEditable()),
            ActionGroup::make([
                Action::make('cancel')
                    ->label(__('sms.actions.cancel'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (): bool => $this->campaign()->status === SmsCampaignStatus::Scheduled)
                    ->requiresConfirmation()
                    ->action(fn (Campaigns $campaigns) => $this->run(fn () => $campaigns->cancel($this->campaign()), __('sms.notifications.campaign_cancelled'))),
                Action::make('refreshReports')
                    ->label(__('sms.actions.refresh_reports'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (): bool => in_array($this->campaign()->status, [SmsCampaignStatus::Sending, SmsCampaignStatus::Sent], true))
                    ->action(function (DeliveryReports $reports): void {
                        $changed = $reports->sync($this->campaign()->messages()->getQuery(), limit: 200);
                        $this->campaign()->refreshCounters();
                        Notification::make()->success()->title(__('sms.notifications.refreshed', ['count' => $changed]))->send();
                    }),
                Action::make('retryFailed')
                    ->label(__('sms.actions.retry_failed'))
                    ->icon(Heroicon::OutlinedArrowUturnRight)
                    ->visible(fn (): bool => ! $this->campaign()->status->isEditable() && $this->campaign()->failed_count > 0)
                    ->action(function (Campaigns $campaigns): void {
                        /** @var User $staff */
                        $staff = auth()->user();
                        $copy = $campaigns->duplicate($this->campaign(), $staff, onlyFailed: true);
                        $this->redirect(SmsCampaignResource::getUrl('view', ['record' => $copy]));
                    }),
                Action::make('duplicate')
                    ->label(__('sms.actions.duplicate'))
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->action(function (Campaigns $campaigns): void {
                        /** @var User $staff */
                        $staff = auth()->user();
                        $copy = $campaigns->duplicate($this->campaign(), $staff);
                        $this->redirect(SmsCampaignResource::getUrl('edit', ['record' => $copy]));
                    }),
                Action::make('export')
                    ->label(__('sms.actions.export'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (): bool => $this->campaign()->messages()->exists())
                    ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                        $out = fopen('php://output', 'w');

                        if ($out === false) {
                            return;
                        }

                        fwrite($out, "\xEF\xBB\xBF");
                        fputcsv($out, ['phone', 'status', 'sent_at', 'delivered_at', 'error'], escape: '');

                        $this->campaign()->messages()->orderBy('id')->each(function ($message) use ($out): void {
                            fputcsv($out, [$message->phone, $message->status->value, $message->sent_at?->format('Y-m-d H:i:s'), $message->delivered_at?->format('Y-m-d H:i:s'), $message->error], escape: '');
                        });

                        fclose($out);
                    }, 'sms-campaign-'.$this->campaign()->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8'])),
                DeleteAction::make()->visible(fn (): bool => in_array($this->campaign()->status, [SmsCampaignStatus::Draft, SmsCampaignStatus::Cancelled], true)),
            ]),
        ];
    }

    public function getTitle(): string
    {
        return $this->campaign()->name;
    }

    private function campaign(): SmsCampaign
    {
        /** @var SmsCampaign $campaign */
        $campaign = $this->getRecord();

        return $campaign;
    }

    /**
     * Runs a campaign step and reports the outcome.
     */
    private function run(callable $step, string $success): void
    {
        try {
            $step();
        } catch (RuntimeException $exception) {
            Notification::make()->danger()->title(__('sms.notifications.not_done'))->body($exception->getMessage())->send();

            return;
        }

        $this->getRecord()->refresh();
        Notification::make()->success()->title($success)->send();
    }
}
