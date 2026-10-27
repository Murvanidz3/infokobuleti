<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Exceptions\SmsDeliveryFailed;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * UBill SMS (Georgian provider). The API key travels as the "key" query parameter (and header);
 * numbers go in international format without "+". The sender is a brand name registered and
 * approved in the UBill dashboard: its numeric brandID comes from UBILL_BRAND_ID, or is looked up
 * by name (SMS_SENDER) and cached for a day.
 *
 * @see https://ubill.ge/blog/sms-api
 * @see https://documenter.getpostman.com/view/13830965/TVmV6ZWT
 */
class UbillSmsGateway implements SmsGateway
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

    public function __construct(
        private string $apiKey,
        private string $brandName,
        private ?int $brandId,
        private Cache $cache,
        private string $baseUrl = 'https://api.ubill.dev/v1/sms',
        private bool $otp = true,
    ) {}

    /**
     * Every message this site sends is a one-time sign-in code, so it goes with UBill's "otp"
     * (highest priority) flag.
     */
    public function send(string $to, string $text): ?string
    {
        return $this->deliver($to, $text, $this->otp);
    }

    /**
     * @throws SmsDeliveryFailed
     */
    public function deliver(string $to, string $text, bool $otp): string
    {
        $response = $this->call('send', array_filter([
            'brandID' => $this->brandId(),
            'numbers' => self::number($to),
            'text' => $text,
            'stopList' => 'false',
            'otp' => $otp ? 'true' : null,
        ]));

        if ((int) $response->json('statusID', 99) !== 0 || blank($response->json('smsID'))) {
            throw new SmsDeliveryFailed($this->error($response));
        }

        return (string) $response->json('smsID');
    }

    /**
     * SMS left on the account balance.
     *
     * @throws SmsDeliveryFailed
     */
    public function balance(): int
    {
        $response = $this->call('balance');

        if ((int) $response->json('statusID', 99) !== 0) {
            throw new SmsDeliveryFailed($this->error($response));
        }

        return (int) $response->json('sms', 0);
    }

    /**
     * Brand names of the account.
     *
     * @return list<array{id: int, name: string, authorized: bool}>
     *
     * @throws SmsDeliveryFailed
     */
    public function brands(): array
    {
        $response = $this->call('brandNames');

        if ((int) $response->json('statusID', 99) !== 0) {
            throw new SmsDeliveryFailed($this->error($response));
        }

        return array_values(array_map(fn (array $brand): array => [
            'id' => (int) ($brand['id'] ?? 0),
            'name' => (string) ($brand['name'] ?? ''),
            'authorized' => (string) ($brand['authorized'] ?? '0') === '1',
        ], array_filter((array) $response->json('data', []), 'is_array')));
    }

    /**
     * Delivery status of a sent message: 0 sent, 1 received, 2 not delivered, 3 pending, 4 error.
     *
     * @return array<string, int> number => statusID
     *
     * @throws SmsDeliveryFailed
     */
    public function report(string $smsId): array
    {
        $response = $this->call('report/'.rawurlencode($smsId));

        if ((int) $response->json('statusID', 99) !== 0) {
            throw new SmsDeliveryFailed($this->error($response));
        }

        $report = [];

        foreach ((array) $response->json('result', []) as $row) {
            if (is_array($row) && isset($row['number'])) {
                $report[(string) $row['number']] = (int) ($row['statusID'] ?? 4);
            }
        }

        return $report;
    }

    /**
     * The brandID: configured, or the authorized brand whose name matches the sender.
     *
     * @throws SmsDeliveryFailed
     */
    public function brandId(): int
    {
        if ($this->brandId !== null) {
            return $this->brandId;
        }

        $key = 'sms:ubill:brand:'.md5($this->apiKey.'|'.mb_strtolower($this->brandName));
        $cached = $this->cache->get($key);

        if (is_int($cached)) {
            return $cached;
        }

        $brand = collect($this->brands())->first(fn (array $brand): bool => $brand['authorized'] && mb_strtolower($brand['name']) === mb_strtolower($this->brandName));

        if ($brand === null) {
            throw new SmsDeliveryFailed("UBill: no authorized brand name \"{$this->brandName}\" on this account.");
        }

        $this->cache->put($key, $brand['id'], now()->addDay());

        return $brand['id'];
    }

    /** International format without "+" or "00": +995 555 12 34 56 → 995555123456. */
    public static function number(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);

        return str_starts_with($digits, '00') ? substr($digits, 2) : $digits;
    }

    /**
     * @param  array<string, scalar>  $query
     *
     * @throws SmsDeliveryFailed
     */
    private function call(string $path, array $query = []): Response
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders(['key' => $this->apiKey])
                ->timeout(10)
                ->get(rtrim($this->baseUrl, '/').'/'.$path, ['key' => $this->apiKey] + $query);
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

    /** The key travels in the URL, so it must never end up in a stored error message. */
    private function hideKey(string $text): string
    {
        return $this->apiKey === '' ? $text : str_replace($this->apiKey, '***', $text);
    }
}
