<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * Resolves SMS drivers by name (config/sms.php): log and array for development and tests, ubill
 * (Georgian provider, production) and twilio (fallback).
 *
 * @method SmsGateway driver(string|null $driver = null)
 */
class SmsManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('sms.default', 'log');
    }

    protected function createLogDriver(): SmsGateway
    {
        return new LogSmsGateway($this->config->get('sms.drivers.log.channel'));
    }

    protected function createArrayDriver(): SmsGateway
    {
        return new ArraySmsGateway;
    }

    protected function createUbillDriver(): UbillSmsGateway
    {
        $config = $this->config->get('sms.drivers.ubill');

        if (blank($config['api_key'] ?? null)) {
            throw new InvalidArgumentException('UBill needs UBILL_API_KEY.');
        }

        return new UbillSmsGateway(
            apiKey: (string) $config['api_key'],
            brandName: (string) ($config['brand_name'] ?? $this->config->get('sms.sender')),
            brandId: filled($config['brand_id'] ?? null) ? (int) $config['brand_id'] : null,
            cache: $this->container->make('cache.store'),
            baseUrl: (string) ($config['base_url'] ?? 'https://api.ubill.dev/v1/sms'),
        );
    }

    protected function createTwilioDriver(): SmsGateway
    {
        $config = $this->config->get('sms.drivers.twilio');

        if (blank($config['account_sid'] ?? null) || blank($config['auth_token'] ?? null)) {
            throw new InvalidArgumentException('Twilio needs TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN.');
        }

        return new TwilioSmsGateway(
            accountSid: $config['account_sid'],
            authToken: $config['auth_token'],
            from: $config['from'] ?? null,
            messagingServiceSid: $config['messaging_service_sid'] ?? null,
            statusCallback: ($config['status_callback'] ?? false) ? route('webhooks.sms.twilio') : null,
        );
    }
}
