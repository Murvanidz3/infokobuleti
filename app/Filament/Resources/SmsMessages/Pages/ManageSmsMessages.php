<?php

namespace App\Filament\Resources\SmsMessages\Pages;

use App\Filament\Resources\SmsMessages\SmsMessageResource;
use App\Filament\Support\SmsFields;
use App\Models\SmsMessage;
use App\Support\GeorgianPhone;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManageSmsMessages extends ManageRecords
{
    protected static string $resource = SmsMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SmsFields::sendAction(),
            Action::make('export')
                ->label(__('sms.actions.export'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $out = fopen('php://output', 'w');

                    if ($out === false) {
                        return;
                    }

                    fwrite($out, "\xEF\xBB\xBF");
                    fputcsv($out, ['created_at', 'phone', 'kind', 'status', 'campaign_id', 'segments', 'sent_at', 'delivered_at', 'text', 'error'], escape: '');

                    // What the table shows with its current filters and search, newest first.
                    $this->getFilteredSortedTableQuery()?->limit(50000)->each(function (Model $message) use ($out): void {
                        if (! $message instanceof SmsMessage) {
                            return;
                        }

                        fputcsv($out, [
                            $message->created_at?->format('Y-m-d H:i:s'),
                            GeorgianPhone::format($message->phone),
                            $message->kind()->value,
                            $message->status->value,
                            $message->campaign_id,
                            $message->segments,
                            $message->sent_at?->format('Y-m-d H:i:s'),
                            $message->delivered_at?->format('Y-m-d H:i:s'),
                            $message->isOneTimeCode() ? '' : $message->body,
                            $message->error,
                        ], escape: '');
                    });

                    fclose($out);
                }, 'sms-history-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8'])),
        ];
    }
}
