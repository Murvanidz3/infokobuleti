<?php

namespace App\Console\Commands;

use App\Exceptions\SmsDeliveryFailed;
use App\Services\Sms\SmsManager;
use App\Services\Sms\UbillSmsGateway;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Checks the UBill account from the server (the API key only works from the server's IP):
 * brand names, the brand used for sending, the SMS balance, and optionally sends a test SMS.
 */
#[Signature('sms:ubill-status {--send= : Send a test SMS to this number (e.g. 995555123456)} {--report= : Show the delivery report of an smsID}')]
#[Description('Show UBill brand names and SMS balance, optionally send a test SMS')]
class SmsUbillStatus extends Command
{
    public function handle(SmsManager $sms): int
    {
        try {
            $gateway = $sms->driver('ubill');
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $gateway instanceof UbillSmsGateway) {
            return self::FAILURE;
        }

        try {
            $this->table(['ID', 'Brand name', 'Authorized'], array_map(fn (array $brand): array => [$brand['id'], $brand['name'], $brand['authorized'] ? 'yes' : 'no'], $gateway->brands()));
            $this->line('Brand used for sending: '.config('sms.drivers.ubill.brand_name').' (brandID '.$gateway->brandId().')');
            $this->line('SMS balance: '.$gateway->balance());

            if (filled($smsId = $this->option('report'))) {
                foreach ($gateway->report((string) $smsId) as $number => $status) {
                    $this->line("Report {$smsId}: {$number} status {$status} (0 sent, 1 received, 2 not delivered, 3 pending, 4 error)");
                }
            }

            if (filled($number = $this->option('send'))) {
                $smsId = $gateway->send((string) $number, 'infokobuleti.com: SMS test '.now()->format('H:i'));
                $this->info("Test SMS sent to {$number}, smsID {$smsId}. Check it later with --report={$smsId}");
            }
        } catch (SmsDeliveryFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Driver in use: '.config('sms.default'));

        return self::SUCCESS;
    }
}
