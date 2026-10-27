<?php

namespace App\Models;

use App\Enums\SmsAudience;
use App\Enums\SmsCampaignStatus;
use App\Enums\SmsStatus;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A bulk send. Recipients are resolved when the campaign is saved (a snapshot of numbers and
 * names); the messages are created in the delivery log when it goes out.
 *
 * @property list<array{phone: string, name?: string|null, user_id?: int|null}>|null $recipients
 * @property array<string, mixed>|null $audience_options
 * @property array{invalid?: list<string>, invalid_count?: int, duplicates?: int, opted_out?: int, rows?: int, sms_total?: int, over_limit?: int}|null $import_report
 */
#[Guarded(['id'])]
class SmsCampaign extends Model
{
    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        static::deleted(function (SmsCampaign $campaign): void {
            if (filled($path = $campaign->audience_options['csv'] ?? null)) {
                Storage::disk('local')->delete((string) $path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => SmsCampaignStatus::class,
            'audience' => SmsAudience::class,
            'audience_options' => 'array',
            'recipients' => 'array',
            'import_report' => 'array',
            'respect_stop_list' => 'boolean',
            'append_footer' => 'boolean',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * The campaign's messages (test sends not included).
     *
     * @return HasMany<SmsMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'campaign_id')->where('purpose', SmsMessage::PURPOSE_CAMPAIGN);
    }

    /**
     * @return HasMany<SmsMessage, $this>
     */
    public function testMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'campaign_id')->where('purpose', SmsMessage::PURPOSE_TEST);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** SMS the whole campaign takes from the balance (parts × recipients). */
    public function estimatedSms(): int
    {
        return (int) ($this->import_report['sms_total'] ?? $this->segments * $this->recipients_count);
    }

    /** Counters from the delivery log. */
    public function refreshCounters(): void
    {
        $counts = $this->messages()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $this->forceFill([
            'sent_count' => (int) ($counts[SmsStatus::Sent->value] ?? 0) + (int) ($counts[SmsStatus::Delivered->value] ?? 0),
            'delivered_count' => (int) ($counts[SmsStatus::Delivered->value] ?? 0),
            'failed_count' => (int) ($counts[SmsStatus::Failed->value] ?? 0),
        ])->save();
    }

    public function deliveryRate(): ?float
    {
        return $this->sent_count > 0 ? round($this->delivered_count / $this->sent_count * 100, 1) : null;
    }
}
