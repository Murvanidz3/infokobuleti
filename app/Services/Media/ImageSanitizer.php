<?php

namespace App\Services\Media;

use RuntimeException;

/**
 * Re-encodes an uploaded photo with GD: applies the camera orientation, limits the size and drops
 * all metadata (EXIF, GPS position, camera serials) before the file is stored.
 */
class ImageSanitizer
{
    public const int MAX_EDGE = 2560;

    public const int QUALITY = 85;

    /**
     * Sanitizes the file in place and returns its (possibly new) MIME type.
     *
     * @throws RuntimeException when the file is not a readable JPEG, PNG or WebP image
     */
    public function sanitize(string $path): string
    {
        $info = @getimagesize($path);
        $mime = $info['mime'] ?? null;

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException('Unsupported or broken image.');
        }

        if ($mime === 'image/jpeg') {
            $image = $this->orient($image, $path);
        }

        $image = $this->limit($image);

        $written = match ($mime) {
            'image/png' => $this->writePng($image, $path),
            'image/webp' => imagewebp($image, $path, self::QUALITY),
            default => imagejpeg($image, $path, self::QUALITY),
        };

        imagedestroy($image);

        if (! $written) {
            throw new RuntimeException('Could not write the image.');
        }

        return (string) $mime;
    }

    private function orient(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        // Mirrored orientations (selfie cameras); 5 and 7 are practically never written.
        match ($orientation) {
            2 => imageflip($image, IMG_FLIP_HORIZONTAL),
            4 => imageflip($image, IMG_FLIP_VERTICAL),
            default => null,
        };

        return $rotated instanceof \GdImage ? $rotated : $image;
    }

    private function limit(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest <= self::MAX_EDGE) {
            return $image;
        }

        $scaled = imagescale($image, (int) round($width * self::MAX_EDGE / $longest), (int) round($height * self::MAX_EDGE / $longest), IMG_BICUBIC);

        return $scaled instanceof \GdImage ? $scaled : $image;
    }

    private function writePng(\GdImage $image, string $path): bool
    {
        imagesavealpha($image, true);

        return imagepng($image, $path, 7);
    }
}
