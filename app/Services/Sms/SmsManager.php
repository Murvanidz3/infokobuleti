<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * Resolves SMS drivers by name (config/sms.php). A Georgian provider is added here as another
 * create{Name}Driver method once its API documentation is available.
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
