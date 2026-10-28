<?php

namespace App\Filament\Resources\SmsOptOuts\Pages;

use App\Filament\Resources\SmsOptOuts\SmsOptOutResource;
use App\Models\SmsOptOut;
use App\Services\Sms\RecipientImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ManageSmsOptOuts extends ManageRecords
{
    protected static string $resource = SmsOptOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'created_by' => auth()->id()]),
            Action::make('import')
                ->label(__('sms.actions.import'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->schema([
                    Textarea::make('numbers')->label(__('sms.fields.numbers'))->rows(5)->helperText(__('sms.campaigns.numbers_help')),
                    FileUpload::make('csv')->label(__('sms.fields.csv'))->disk('local')->directory('sms-imports')->visibility('private')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'text/x-csv'])->maxSize(5120),
                    TextInput::make('reason')->label(__('sms.fields.reason'))->maxLength(255),
                ])
                ->action(function (array $data, RecipientImport $import): void {
                    $recipients = $import->fromText((string) ($data['numbers'] ?? ''), skipOptOuts: false)['recipients'];

                    if (filled($data['csv'] ?? null)) {
                        $path = (string) $data['csv'];
                        $recipients = [...$recipients, ...$import->fromCsv(Storage::disk('local')->path($path), skipOptOuts: false)['recipients']];
                        Storage::disk('local')->delete($path);
                    }

                    $added = 0;

                    foreach (array_unique(array_column($recipients, 'phone')) as $phone) {
                        $added += SmsOptOut::query()->firstOrCreate(['phone' => $phone], ['reason' => $data['reason'] ?? null, 'created_by' => auth()->id()])->wasRecentlyCreated ? 1 : 0;
                    }

                    Notification::make()->success()->title(__('sms.notifications.opt_outs_imported', ['count' => $added]))->send();
                }),
        ];
    }
}
