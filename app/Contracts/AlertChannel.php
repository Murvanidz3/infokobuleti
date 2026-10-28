<?php

namespace App\Contracts;

/**
 * Where system alerts go (log file, Telegram, later email).
 */
interface AlertChannel
{
    public function enabled(): bool;

    public function send(string $title, string $text): void;
}
