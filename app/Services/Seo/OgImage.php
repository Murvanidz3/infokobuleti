<?php

namespace App\Services\Seo;

use GdImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Share pictures (1200×630) for social networks and messengers: the cover photo with a navy band,
 * the title, a price or date line and the logo. Made on first request and kept on the public disk;
 * the URL carries a version, so a changed title or photo gets a new picture.
 */
final class OgImage
{
    public const int WIDTH = 1200;

    public const int HEIGHT = 630;

    private const string DIRECTORY = 'og';

    /** Design tokens: navy, white, sun (the dot of the logo). */
    private const array NAVY = [10, 26, 47];

    private const array SUN = [232, 112, 58];

    /**
     * @param  array<string, string|null>  $vars
     */
    public function url(Model $model, array $vars): string
    {
        return url(sprintf('/og/%s/%d-%s.jpg', $model->getMorphClass(), $model->getKey(), app()->getLocale())).'?v='.$this->version($model, $vars);
    }

    /**
     * Path of the picture on the public disk, created when missing.
     *
     * @param  array<string, string|null>  $vars
     */
    public function path(Model $model, array $vars): string
    {
        $path = sprintf('%s/%s-%d-%s-%s.jpg', self::DIRECTORY, $model->getMorphClass(), $model->getKey(), app()->getLocale(), $this->version($model, $vars));
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            foreach ($disk->files(self::DIRECTORY) as $old) {
                if (str_starts_with(basename($old), sprintf('%s-%d-%s-', $model->getMorphClass(), $model->getKey(), app()->getLocale()))) {
                    $disk->delete($old);
                }
            }

            $disk->put($path, $this->render($this->title($model), $this->subtitle($vars), $this->coverPath($model)));
        }

        return $disk->path($path);
    }

    /**
     * The JPEG bytes.
     */
    public function render(string $title, string $subtitle, ?string $cover): string
    {
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $navy = imagecolorallocate($canvas, ...self::NAVY);
        imagefilledrectangle($canvas, 0, 0, self::WIDTH, self::HEIGHT, $navy);

        if ($cover !== null && ($photo = $this->load($cover)) !== null) {
            $this->cover($canvas, $photo);
            imagedestroy($photo);
            $this->shade($canvas);
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $muted = imagecolorallocatealpha($canvas, 255, 255, 255, 40);
        $sun = imagecolorallocate($canvas, ...self::SUN);
        $bold = resource_path('fonts/Invoice-Bold.ttf');
        $regular = resource_path('fonts/Invoice-Regular.ttf');

        $lines = $this->wrap($title, $bold, 52, self::WIDTH - 140, 2);
        $y = self::HEIGHT - 70 - ($subtitle !== '' ? 52 : 0) - (count($lines) - 1) * 66;

        foreach ($lines as $line) {
            imagettftext($canvas, 52, 0, 70, $y, $white, $bold, $line);
            $y += 66;
        }

        if ($subtitle !== '') {
            imagettftext($canvas, 28, 0, 70, $y - 6, $muted, $regular, $this->fit($subtitle, $regular, 28, self::WIDTH - 140));
        }

        $this->logo($canvas, $white, $muted, $sun);

        ob_start();
        imagejpeg($canvas, null, 84);
        imagedestroy($canvas);

        return (string) ob_get_clean();
    }

    /**
     * @param  array<string, string|null>  $vars
     */
    private function version(Model $model, array $vars): string
    {
        $cover = $model instanceof HasMedia ? $model->getFirstMedia('gallery') : null;

        return substr(md5(implode('|', [$this->title($model), $this->subtitle($vars), $cover?->getKey(), $cover?->updated_at?->timestamp])), 0, 10);
    }

    private function title(Model $model): string
    {
        return (string) ($model->getAttribute('title') ?? $model->getAttribute('name'));
    }

    /**
     * @param  array<string, string|null>  $vars
     */
    private function subtitle(array $vars): string
    {
        return implode('  ·  ', array_filter([$vars['price'] ?? null, $vars['date'] ?? null, $vars['location'] ?? null], fn (?string $part): bool => filled($part)));
    }

    private function coverPath(Model $model): ?string
    {
        if (! $model instanceof HasMedia) {
            return null;
        }

        $media = $model->getFirstMedia('gallery');

        if (! $media instanceof Media) {
            return null;
        }

        foreach (['large', 'card'] as $conversion) {
            if ($media->hasGeneratedConversion($conversion) && is_file($path = $media->getPath($conversion))) {
                return $path;
            }
        }

        return is_file($media->getPath()) ? $media->getPath() : null;
    }

    private function load(string $path): ?GdImage
    {
        $info = @getimagesize($path);

        $image = match ($info === false ? null : $info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        return $image instanceof GdImage ? $image : null;
    }

    /**
     * Fills the canvas with the photo (center crop).
     */
    private function cover(GdImage $canvas, GdImage $photo): void
    {
        $width = imagesx($photo);
        $height = imagesy($photo);
        $scale = max(self::WIDTH / $width, self::HEIGHT / $height);
        $cropWidth = (int) round(self::WIDTH / $scale);
        $cropHeight = (int) round(self::HEIGHT / $scale);

        imagecopyresampled($canvas, $photo, 0, 0, (int) (($width - $cropWidth) / 2), (int) (($height - $cropHeight) / 2), self::WIDTH, self::HEIGHT, $cropWidth, $cropHeight);
    }

    /**
     * Navy gradient from the bottom, so white text stays readable on any photo.
     */
    private function shade(GdImage $canvas): void
    {
        imagealphablending($canvas, true);

        // One alpha per row: dark at the top (logo), clear in the upper middle, dark at the bottom (text).
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $position = $y / self::HEIGHT;
            $top = max(0.0, 0.55 - $position * 2.2);
            $bottom = max(0.0, min(0.92, ($position - 0.3) * 1.6));
            $alpha = (int) round(127 - max($top, $bottom) * 127);
            imageline($canvas, 0, $y, self::WIDTH, $y, imagecolorallocatealpha($canvas, ...[...self::NAVY, $alpha]));
        }
    }

    /**
     * "infokobuleti" wordmark, top left: light "info", bold "kobulet", dotless i with the sun dot.
     */
    private function logo(GdImage $canvas, int $white, int $muted, int $sun): void
    {
        $light = resource_path('fonts/Outfit-300.ttf');
        $bold = resource_path('fonts/Outfit-700.ttf');
        $size = 34;
        $x = 70;
        $y = 82;

        $box = imagettftext($canvas, $size, 0, $x, $y, $muted, $light, 'info');
        $x = $box[2] + 1;
        $box = imagettftext($canvas, $size, 0, $x, $y, $white, $bold, 'kobulet');
        $x = $box[2] + 1;
        $box = imagettftext($canvas, $size, 0, $x, $y, $white, $bold, 'ı');
        $center = (int) (($x + $box[2]) / 2) - 1;
        imagefilledellipse($canvas, $center, $y - 33, 11, 11, $sun);
    }

    /**
     * Word wrap into at most $max lines, the last one shortened with an ellipsis.
     *
     * @return list<string>
     */
    private function wrap(string $text, string $font, int $size, int $width, int $max): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $index => $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;

            if ($this->width($candidate, $font, $size) <= $width || $line === '') {
                $line = $candidate;

                continue;
            }

            $lines[] = $line;
            $line = $word;

            if (count($lines) === $max - 1) {
                $line = implode(' ', array_slice($words, $index));
                break;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        $lines = array_slice($lines, 0, $max);
        $last = count($lines) - 1;

        if ($last >= 0) {
            $lines[$last] = $this->fit($lines[$last], $font, $size, $width);
        }

        return $lines;
    }

    private function fit(string $text, string $font, int $size, int $width): string
    {
        if ($this->width($text, $font, $size) <= $width) {
            return $text;
        }

        while (mb_strlen($text) > 1 && $this->width($text.'…', $font, $size) > $width) {
            $text = mb_substr($text, 0, -1);
        }

        return rtrim($text).'…';
    }

    private function width(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box === false ? 0 : abs($box[2] - $box[0]);
    }
}
