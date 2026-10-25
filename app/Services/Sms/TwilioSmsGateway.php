<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Exceptions\SmsDeliveryFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Twilio Programmable Messaging (fallback provider).
 *
 * @see https://www.twilio.com/docs/messaging/api/message-resource#create-a-message-resource
 */
class TwilioSmsGateway implements SmsGateway
{
    public function __construct(
        private string $accountSid,
        private string $authToken,
        private ?string $from,
        private ?string $messagingServiceSid = null,
        private ?string $statusCallback = null,
    ) {}

    public function send(string $to, string $text): ?string
    {
        $payload = array_filter([
            'To' => $to,
            'Body' => $text,
            'MessagingServiceSid' => $this->messagingServiceSid,
            'From' => $this->messagingServiceSid ? null : $this->from,
            'StatusCallback' => $this->statusCallback,
        ]);

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->accountSid, $this->authToken)
                ->timeout(10)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", $payload);
        } catch (ConnectionException $exception) {
            throw new SmsDeliveryFailed('Twilio is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new SmsDeliveryFailed(sprintf('Twilio error %s: %s', $response->json('code', $response->status()), $response->json('message', $response->body())));
        }

        return $response->json('sid');
    }
}
