<?php

namespace App\Services\Sms;

use App\Contracts\SendsBulkSms;
use App\Contracts\SmsGateway;
use App\Contracts\SmsProviderAccount;
use App\Exceptions\SmsDeliveryFailed;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * UBill SMS (Georgian provider). The API key travels in the "key" header; numbers go in
 * international format without "+". The sender is a brand name registered and approved in the
 * UBill dashboard: its numeric brandID comes from UBILL_BRAND_ID, or is looked up by name
 * (SMS_SENDER) and cached for a day.
 *
 * The live API differs from the published examples in places (brand names come as "brands"
 * with an integer "authorized": 0 pending, 1 approved, 2 cancelled), so both shapes are read.
 *
 * @see https://ubill.ge/blog/sms-api
 * @see https://documenter.getpostman.com/view/13830965/TVmV6ZWT
 */
class UbillSmsGateway implements SendsBulkSms, SmsGateway, SmsProviderAccount
{
    /** Error codes of the send call. */
    private const array ERRORS = [
        10 => 'brandID not found',
        20 => 'numbers not found',
        30 => 'empty text',
        40 => 'not enough SMS on the balance',
        50 => 'no valid numbers',
        60 => 'invalid sendTime',
        90 => 'JSON error',
        99 => 'general error',
    ];

    /** UBill reads sendTime in Georgian time. */
    private const string TIMEZONE = 'Asia/Tbilisi';

    public function __construct(
        private string $apiKey,
        private string $brandName,
        private ?int $brandId,
        private Cache $cache,
        private string $baseUrl = 'https://api.ubill.dev/v1/sms',
    ) {}

    public function send(string $to, string $text, SmsOptions $options = new SmsOptions): ?string
    {
        return $this->deliver(['numbers' => [self::number($to)], 'text' => $text], $options);
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function sendBatch(array $messages, SmsOptions $options = new SmsOptions): ?string
    {
        if ($messages === []) {
            return null;
        }

        $texts = array_unique(array_column($messages, 'text'));

        // One text for everybody: a plain list of numbers. Otherwise a text per number.
        $payload = count($texts) === 1
            ? ['numbers' => array_map(fn (array $message): string => self::number($message['phone']), $messages), 'text' => (string) reset($texts)]
            : ['numbers' => array_map(fn (array $message): array => ['number' => self::number($message['phone']), 'text' => $message['text']], $messages)];

        return $this->deliver($payload, $options, timeout: 60);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws SmsDeliveryFailed
     */
    private function deliver(array $payload, SmsOptions $options, int $timeout = 15): string
    {
        $payload = ['brandID' => $this->brandId()] + $payload + ['stopList' => $options->respectStopList];

        if ($options->otp) {
            $payload['otp'] = true;
        }

        if ($options->sendAt !== null) {
            $payload['sendTime'] = $options->sendAt->copy()->setTimezone(self::TIMEZONE)->format('Y-m-d H:i');
        }

        $response = $this->request('post', 'send', $payload, $timeout);

        if ((int) $response->json('statusID', 99) !== 0 || blank($response->json('smsID'))) {
            throw new SmsDeliveryFailed($this->error($response));
        }

        return (string) $response->json('smsID');
    }

    public function balance(): int
    {
        $response = $this->request('get', 'balance');

        if ((int) $response->json('statusID', 99) !== 0) {
            throw new SmsDeliveryFailed($this->error($response));
        }

        return (int) $response->json('sms', 0);
    }

    public function brands(): array
    {
        $response = $this->request('get', 'brandNames');

        if ((int) $response->json('statusID', 99) !== 0) {
            throw new SmsDeliveryFailed($this->error($response));
        }

        $rows = $response->json('brands') ?? $response->json('data') ?? [];

        return array_values(array_map(fn (array $brand): array => [
            'id' => (int) ($brand['id'] ?? 0),
            'name' => (string) ($brand['name'] ?? ''),
            'authorized' => (int) ($brand['authorized'] ?? 0) === 1,
            'status' => (int) ($brand['authorized'] ?? 0),
        ], array_filter(is_array($rows) ? $rows : [], 'is_array')));
    }

    public function report(string $providerMessageId): array
    {
        $response = $this->request('get', 'report/'.rawurlencode($providerMessageId));

        if ((int) $response->json('statusID', 99) !== 0) {
            throw new SmsDeliveryFailed($this->error($response));
        }

        $report = [];

        foreach ((array) $response->json('result', []) as $row) {
            if (is_array($row) && isset($row['number'])) {
                $report[self::local((string) $row['number'])] = (int) ($row['statusID'] ?? self::REPORT_ERROR);
            }
        }

        return $report;
    }

    /**
     * The brandID: configured, or the approved brand whose name matches the sender.
     *
     * @throws SmsDeliveryFailed
     */
    public function brandId(): int
    {
        if ($this->brandId !== null) {
            return $this->brandId;
        }

        $cached = $this->cache->get($this->brandCacheKey());

        if (is_int($cached)) {
            return $cached;
        }

        $brand = collect($this->brands())->first(fn (array $brand): bool => $brand['authorized'] && mb_strtolower($brand['name']) === mb_strtolower($this->brandName));

        if ($brand === null) {
            throw new SmsDeliveryFailed("UBill: no approved brand name \"{$this->brandName}\" on this account.");
        }

        $this->cache->put($this->brandCacheKey(), $brand['id'], now()->addDay());

        return $brand['id'];
    }

    /** After a brand name was approved or changed in UBill. */
    public function forgetBrand(): void
    {
        $this->cache->forget($this->brandCacheKey());
    }

    public function brandName(): string
    {
        return $this->brandName;
    }

    /** International format without "+" or "00": +995 555 12 34 56 → 995555123456. */
    public static function number(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);

        return str_starts_with($digits, '00') ? substr($digits, 2) : $digits;
    }

    /** The last nine digits, the part every format of a Georgian mobile number shares. */
    public static function local(string $phone): string
    {
        return substr((string) preg_replace('/\D+/', '', $phone), -9);
    }

    private function brandCacheKey(): string
    {
        return 'sms:ubill:brand:'.md5($this->apiKey.'|'.mb_strtolower($this->brandName));
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws SmsDeliveryFailed
     */
    private function request(string $method, string $path, array $data = [], int $timeout = 15): Response
    {
        $url = rtrim($this->baseUrl, '/').'/'.$path;

        try {
            $client = Http::acceptJson()->withHeaders(['key' => $this->apiKey])->timeout($timeout);
            $response = $method === 'post' ? $client->asJson()->post($url, $data) : $client->get($url, $data);
        } catch (ConnectionException $exception) {
            throw new SmsDeliveryFailed('UBill is unreachable: '.$this->hideKey($exception->getMessage()), previous: $exception);
        }

        if ($response->serverError() || ($response->failed() && ! is_array($response->json()))) {
            throw new SmsDeliveryFailed('UBill HTTP '.$response->status().': '.$this->hideKey(mb_substr($response->body(), 0, 200)));
        }

        return $response;
    }

    private function error(Response $response): string
    {
        $status = (int) $response->json('statusID', 99);
        $message = (string) ($response->json('message') ?: (self::ERRORS[$status] ?? 'unknown error'));

        return $this->hideKey("UBill error {$status}: {$message}");
    }

    /** The key must never end up in a stored error message. */
    private function hideKey(string $text): string
    {
        return $this->apiKey === '' ? $text : str_replace($this->apiKey, '***', $text);
    }
}
