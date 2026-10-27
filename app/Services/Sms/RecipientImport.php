<?php

namespace App\Services\Sms;

use App\Enums\AccountType;
use App\Models\SmsOptOut;
use App\Models\User;
use App\Support\GeorgianPhone;
use Illuminate\Support\Carbon;

/**
 * Turns what staff typed or uploaded into a clean recipient list: Georgian mobile numbers in
 * E.164, without duplicates and without numbers on the opt-out list, plus a report of what was
 * dropped.
 *
 * @phpstan-type Recipient array{phone: string, name: string|null, user_id: int|null}
 * @phpstan-type Result array{recipients: list<Recipient>, report: array{rows: int, invalid: list<string>, invalid_count: int, duplicates: int, opted_out: int}}
 */
class RecipientImport
{
    /** Header names of the phone and name columns in a CSV file, besides lang/{locale}/sms.php "csv_headers". */
    private const array PHONE_HEADERS = ['phone', 'mobile', 'number', 'tel', 'telephone'];

    private const array NAME_HEADERS = ['name', 'full name', 'first name'];

    /** Invalid entries kept in the report (the count is always complete). */
    private const int INVALID_SAMPLE = 50;

    /**
     * Numbers separated by new lines, commas, semicolons or spaces between numbers.
     *
     * @return Result
     */
    public function fromText(string $text, bool $skipOptOuts = true): array
    {
        $rows = [];

        foreach (preg_split('/[\r\n,;]+/', $text) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // "555 12 34 56" is one number, "555123456 577123456" are two.
            $parts = GeorgianPhone::isValid($line) ? [$line] : (preg_split('/\s+/', $line) ?: []);

            foreach ($parts as $part) {
                $rows[] = ['phone' => $part, 'name' => null, 'user_id' => null];
            }
        }

        return $this->clean($rows, $skipOptOuts);
    }

    /**
     * A CSV / TXT file: the phone column is found by its header or by its values; an optional
     * name column by its header (or the first other text column).
     *
     * @return Result
     */
    public function fromCsv(string $path, bool $skipOptOuts = true): array
    {
        $content = (string) @file_get_contents($path, length: 5 * 1024 * 1024);
        $content = (string) preg_replace('/^\xEF\xBB\xBF/', '', $content);

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = (string) mb_convert_encoding($content, 'UTF-8', 'Windows-1251');
        }

        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', $content) ?: [], fn (string $line): bool => trim($line) !== ''));

        if ($lines === []) {
            return $this->clean([], $skipOptOuts);
        }

        $delimiter = $this->delimiter(array_slice($lines, 0, 20));
        $table = array_map(fn (string $line): array => array_map(fn ($cell): string => trim((string) $cell), str_getcsv($line, $delimiter, '"', '')), $lines);

        $header = array_map(fn (string $cell): string => mb_strtolower($cell), $table[0]);
        $hasHeader = collect($header)->contains(fn (string $cell): bool => in_array($cell, self::headers('phone'), true) || in_array($cell, self::headers('name'), true))
            || ! collect($table[0])->contains(fn (string $cell): bool => GeorgianPhone::isValid($cell));

        $body = $hasHeader ? array_slice($table, 1) : $table;
        $phoneColumn = $this->phoneColumn($hasHeader ? $header : [], $body);
        $nameColumn = $this->nameColumn($hasHeader ? $header : [], $body, $phoneColumn);

        $rows = array_map(fn (array $row): array => [
            'phone' => (string) ($row[$phoneColumn] ?? ''),
            'name' => $nameColumn !== null && filled($row[$nameColumn] ?? null) ? mb_substr((string) $row[$nameColumn], 0, 60) : null,
            'user_id' => null,
        ], $body);

        return $this->clean($rows, $skipOptOuts);
    }

    /**
     * Registered users with a verified phone who are not blocked.
     *
     * @param  array{account_type?: string|null, registered_from?: string|null, registered_until?: string|null, locale?: string|null}  $filters
     * @return Result
     */
    public function fromUsers(array $filters, bool $skipOptOuts = true): array
    {
        $rows = User::query()
            ->whereNotNull('phone')
            ->whereNotNull('phone_verified_at')
            ->where('is_blocked', false)
            ->when(filled($filters['account_type'] ?? null) && AccountType::tryFrom((string) $filters['account_type']) !== null, fn ($query) => $query->where('account_type', $filters['account_type']))
            ->when(filled($filters['locale'] ?? null), fn ($query) => $query->where('locale', $filters['locale']))
            ->when(filled($filters['registered_from'] ?? null), fn ($query) => $query->where('created_at', '>=', Carbon::parse((string) $filters['registered_from'])->startOfDay()))
            ->when(filled($filters['registered_until'] ?? null), fn ($query) => $query->where('created_at', '<=', Carbon::parse((string) $filters['registered_until'])->endOfDay()))
            ->orderBy('id')
            ->get(['id', 'name', 'phone'])
            ->map(fn (User $user): array => ['phone' => (string) $user->phone, 'name' => $user->name, 'user_id' => $user->id])
            ->all();

        return $this->clean($rows, $skipOptOuts);
    }

    /**
     * @param  list<array{phone: string, name: string|null, user_id: int|null}>  $rows
     * @return Result
     */
    private function clean(array $rows, bool $skipOptOuts): array
    {
        $recipients = [];
        $invalid = [];
        $invalidCount = 0;
        $duplicates = 0;

        foreach ($rows as $row) {
            $phone = GeorgianPhone::normalize($row['phone']);

            if ($phone === null) {
                $invalidCount++;

                if (count($invalid) < self::INVALID_SAMPLE && $row['phone'] !== '') {
                    $invalid[] = mb_substr($row['phone'], 0, 30);
                }

                continue;
            }

            if (isset($recipients[$phone])) {
                $duplicates++;

                continue;
            }

            $recipients[$phone] = ['phone' => $phone, 'name' => $row['name'], 'user_id' => $row['user_id']];
        }

        $optedOut = 0;

        if ($skipOptOuts && $recipients !== []) {
            foreach (array_chunk(array_keys($recipients), 1000) as $chunk) {
                foreach (SmsOptOut::query()->whereIn('phone', $chunk)->pluck('phone') as $phone) {
                    unset($recipients[$phone]);
                    $optedOut++;
                }
            }
        }

        return [
            'recipients' => array_values($recipients),
            'report' => ['rows' => count($rows), 'invalid' => $invalid, 'invalid_count' => $invalidCount, 'duplicates' => $duplicates, 'opted_out' => $optedOut],
        ];
    }

    /**
     * Known header names of a column in every site language, lower case.
     *
     * @return list<string>
     */
    private static function headers(string $column): array
    {
        $names = $column === 'phone' ? self::PHONE_HEADERS : self::NAME_HEADERS;

        foreach (['ka', 'en', 'ru'] as $locale) {
            $names = [...$names, ...(array) trans("sms.csv_headers.{$column}", locale: $locale)];
        }

        return array_values(array_unique(array_map(fn ($name): string => mb_strtolower((string) $name), $names)));
    }

    /**
     * @param  list<string>  $lines
     */
    private function delimiter(array $lines): string
    {
        $scores = [];

        foreach ([',', ';', "\t", '|'] as $candidate) {
            $scores[$candidate] = array_sum(array_map(fn (string $line): int => substr_count($line, $candidate), $lines));
        }

        arsort($scores);

        return (string) array_key_first($scores);
    }

    /**
     * @param  list<string>  $header
     * @param  list<list<string>>  $body
     */
    private function phoneColumn(array $header, array $body): int
    {
        foreach ($header as $index => $cell) {
            if (in_array($cell, self::headers('phone'), true)) {
                return $index;
            }
        }

        // The column where most of the first rows hold a valid number.
        $sample = array_slice($body, 0, 50);
        $best = 0;
        $bestScore = -1;

        foreach (range(0, max(0, max(array_map('count', $sample ?: [[]])) - 1)) as $index) {
            $score = count(array_filter($sample, fn (array $row): bool => GeorgianPhone::isValid($row[$index] ?? null)));

            if ($score > $bestScore) {
                [$best, $bestScore] = [$index, $score];
            }
        }

        return $best;
    }

    /**
     * @param  list<string>  $header
     * @param  list<list<string>>  $body
     */
    private function nameColumn(array $header, array $body, int $phoneColumn): ?int
    {
        foreach ($header as $index => $cell) {
            if ($index !== $phoneColumn && in_array($cell, self::headers('name'), true)) {
                return $index;
            }
        }

        if ($header !== []) {
            return null;
        }

        // Without a header: the first other column that holds text rather than numbers.
        foreach (array_keys($body[0] ?? []) as $index) {
            if ($index !== $phoneColumn && preg_match('/\pL/u', (string) ($body[0][$index] ?? '')) === 1) {
                return $index;
            }
        }

        return null;
    }
}
