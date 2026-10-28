<?php

namespace App\Support\Geo;

/**
 * Distance from a point to the Kobuleti shoreline (config/geo.php), in metres.
 */
final class SeaDistance
{
    private const int EARTH_RADIUS_M = 6371000;

    public static function meters(?float $lat, ?float $lng): ?int
    {
        /** @var list<array{0: float, 1: float}> $coast */
        $coast = config('geo.coastline', []);

        if ($lat === null || $lng === null || count($coast) < 2) {
            return null;
        }

        // Local equirectangular projection around the point: accurate to centimetres at this scale.
        $project = fn (float $pointLat, float $pointLng): array => [
            deg2rad($pointLng - $lng) * cos(deg2rad($lat)) * self::EARTH_RADIUS_M,
            deg2rad($pointLat - $lat) * self::EARTH_RADIUS_M,
        ];

        $best = INF;

        for ($i = 0; $i < count($coast) - 1; $i++) {
            [$ax, $ay] = $project((float) $coast[$i][0], (float) $coast[$i][1]);
            [$bx, $by] = $project((float) $coast[$i + 1][0], (float) $coast[$i + 1][1]);
            $best = min($best, self::distanceToSegment($ax, $ay, $bx, $by));
        }

        $meters = (int) round($best);

        return $meters <= (int) config('geo.max_sea_distance_m', 20000) ? $meters : null;
    }

    /**
     * Distance from the origin to segment AB.
     */
    private static function distanceToSegment(float $ax, float $ay, float $bx, float $by): float
    {
        $dx = $bx - $ax;
        $dy = $by - $ay;
        $lengthSquared = $dx * $dx + $dy * $dy;
        $t = $lengthSquared > 0 ? max(0, min(1, -($ax * $dx + $ay * $dy) / $lengthSquared)) : 0;

        return hypot($ax + $t * $dx, $ay + $t * $dy);
    }
}
