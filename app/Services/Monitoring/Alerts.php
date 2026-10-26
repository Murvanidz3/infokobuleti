<?php

namespace App\Services\Monitoring;

use App\Contracts\AlertChannel;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Sends a system alert to every enabled channel. The same alert ($key) repeats at most once per
 * monitoring.repeat_minutes, so a full disk does not send a message every five minutes.
 */
class Alerts
{
    /**
     * @param  list<AlertChannel>  $channels
     */
    public function __construct(private Cache $cache, private array $channels) {}

    /**
     * @return bool whether the alert was sent (false when it was sent recently)
     */
    public function raise(string $key, string $title, string $text, bool $force = false): bool
    {
        $repeat = max(1, (int) config('monitoring.repeat_minutes', 360));

        if (! $force && ! $this->cache->add('monitoring:alert:'.$key, true, now()->addMinutes($repeat))) {
            return false;
        }

        foreach ($this->channels as $channel) {
            if ($channel->enabled()) {
                $channel->send($title, $text);
            }
        }

        return true;
    }

    /**
     * A problem is gone: the next occurrence alerts again at once.
     */
    public function resolve(string $key): void
    {
        $this->cache->forget('monitoring:alert:'.$key);
    }
}
