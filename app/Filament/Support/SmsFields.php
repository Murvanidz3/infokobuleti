<?php

namespace App\Filament\Support;

use App\Actions\Sms\SendSingleSms;
use App\Enums\Permission;
use App\Enums\SmsStatus;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\Settings;
use App\Support\GeorgianPhone;
use App\Support\SmsText;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Form fields and the "Send SMS" action shared by the SMS manager pages and the users table.
 */
final class SmsFields
{
    /** "45 characters · 1 SMS · Unicode (70 per SMS)". */
    public static function counter(?string $text): string
    {
        $stats = SmsText::stats((string) $text);

        return __('sms.counter', [
            'length' => $stats['length'],
            'segments' => $stats['segments'],
            'encoding' => $stats['unicode'] ? __('sms.encoding.unicode') : __('sms.encoding.gsm'),
            'per' => $stats['per_segment'],
        ]);
    }

    public static function phone(string $name = 'phone'): TextInput
    {
        return TextInput::make($name)
            ->label(__('sms.fields.phone'))
            ->tel()
            ->required()
            ->placeholder('5XX XX XX XX')
            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                if (! GeorgianPhone::isValid(is_string($value) ? $value : null)) {
                    $fail(__('sms.errors.invalid_phone'));
                }
            });
    }

    public static function body(string $name = 'body', bool $placeholders = false): Textarea
    {
        return Textarea::make($name)
            ->label(__('sms.fields.body'))
            ->required()
            ->rows(5)
            ->maxLength(1000)
            ->live(debounce: 400)
            ->helperText(fn (Get $get): string => self::counter($get($name)).($placeholders ? ' · '.__('sms.placeholders', ['list' => implode(' ', SmsText::PLACEHOLDERS)]) : ''));
    }

    /**
     * Picking a template fills the text field.
     */
    public static function template(string $target = 'body'): Select
    {
        return Select::make('template_id')
            ->label(__('sms.fields.template'))
            ->options(fn (): array => SmsTemplate::query()->orderBy('name')->pluck('name', 'id')->all())
            ->searchable()
            ->placeholder(__('sms.fields.template_none'))
            ->dehydrated(false)
            ->live()
            ->afterStateUpdated(function (?string $state, Set $set) use ($target): void {
                if (filled($state) && ($template = SmsTemplate::query()->find($state)) !== null) {
                    $set($target, $template->body);
                }
            });
    }

    /**
     * Send one SMS. On a user record the number is filled in from the account.
     */
    public static function sendAction(string $name = 'sendSms'): Action
    {
        return Action::make($name)
            ->label(__('sms.actions.send_single'))
            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
            ->visible(fn (?Model $record = null): bool => (auth()->user()?->can(Permission::ManageSms->value) ?? false) && (! $record instanceof User || filled($record->phone)))
            ->modalHeading(__('sms.actions.send_single'))
            ->modalSubmitActionLabel(__('sms.actions.send'))
            ->fillForm(fn (?Model $record = null): array => ['phone' => $record instanceof User ? GeorgianPhone::format($record->phone) : null])
            ->schema([
                self::phone(),
                self::template(),
                self::body(),
            ])
            ->action(function (array $data, ?Model $record = null): void {
                /** @var User $staff */
                $staff = auth()->user();
                $message = app(SendSingleSms::class)->handle((string) $data['phone'], (string) $data['body'], $staff, $record instanceof User ? $record : null);

                $message->status === SmsStatus::Failed
                    ? Notification::make()->danger()->title(__('sms.notifications.failed'))->body($message->error)->send()
                    : Notification::make()->success()->title(__('sms.notifications.queued', ['phone' => GeorgianPhone::format($message->phone)]))->send();
            });
    }

    /** The staff member's own number for test sends (setting, else the account's phone). */
    public static function testPhone(): ?string
    {
        $phone = app(Settings::class)->get('sms.test_phone') ?: auth()->user()?->phone;

        return filled($phone) ? GeorgianPhone::format((string) $phone) : null;
    }
}
