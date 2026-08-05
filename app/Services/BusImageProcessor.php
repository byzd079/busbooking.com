<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Turns an uploaded photo into compact WebP bytes for storage in the database.
 *
 * Uses plain GD rather than a wrapper package: GD is already compiled into the
 * production image (see Dockerfile) and the job here is small enough that an
 * extra dependency would not earn its place.
 */
class BusImageProcessor
{
    /** Longest edge of the stored image, in pixels. */
    public const MAX_DIMENSION = 1200;

    /** WebP quality, 0-100. 82 keeps artefacts invisible at roughly a third the size. */
    public const QUALITY = 82;

    /** Reject anything larger than this before decoding, in bytes. */
    public const MAX_UPLOAD_BYTES = 8 * 1024 * 1024;

    /**
     * @return array{data: string, mime: string, size: int, width: int, height: int}
     */
    public function process(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new RuntimeException('The upload did not complete. Please try again.');
        }

        if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
            throw new RuntimeException('That image is larger than 8 MB. Please choose a smaller photo.');
        }

        // Decode from the real file contents, not the client-supplied extension.
        $source = $this->decode($file->getRealPath());

        try {
            $resized = $this->resize($source);

            try {
                $bytes = $this->encodeWebp($resized);

                return [
                    'data'   => $bytes,
                    'mime'   => 'image/webp',
                    'size'   => strlen($bytes),
                    'width'  => imagesx($resized),
                    'height' => imagesy($resized),
                ];
            } finally {
                if ($resized !== $source) {
                    imagedestroy($resized);
                }
            }
        } finally {
            imagedestroy($source);
        }
    }

    /**
     * Decode the file by inspecting its actual bytes. getimagesize() fails on
     * anything that is not really an image, which also screens out files that
     * merely carry an image extension.
     */
    private function decode(string $path): \GdImage
    {
        $info = @getimagesize($path);

        if ($info === false) {
            throw new RuntimeException('That file is not a readable image.');
        }

        [$width, $height] = $info;

        // Guard against decompression bombs: a small file can declare enormous
        // dimensions, and GD allocates roughly 4 bytes per pixel on decode.
        if ($width * $height > 50_000_000) {
            throw new RuntimeException('That image has too many pixels to process.');
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => null,
        };

        if (!$image instanceof \GdImage) {
            throw new RuntimeException('Please upload a JPG, PNG or WebP image.');
        }

        // Phones write orientation into EXIF rather than rotating the pixels, so
        // a portrait photo would otherwise appear on its side in the gallery.
        if ($info[2] === IMAGETYPE_JPEG) {
            $image = $this->applyExifOrientation($image, $path);
        }

        return $image;
    }

    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? null;

        $degrees = match ($orientation) {
            3       => 180,
            6       => -90,
            8       => 90,
            default => 0,
        };

        if ($degrees === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $degrees, 0);

        if (!$rotated instanceof \GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    /** Scale down to fit MAX_DIMENSION, preserving aspect ratio. Never scales up. */
    private function resize(\GdImage $source): \GdImage
    {
        $width  = imagesx($source);
        $height = imagesy($source);
        $longest = max($width, $height);

        if ($longest <= self::MAX_DIMENSION) {
            return $source;
        }

        $scale     = self::MAX_DIMENSION / $longest;
        $newWidth  = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($newWidth, $newHeight);

        if (!$target instanceof \GdImage) {
            throw new RuntimeException('Could not process that image. Please try another photo.');
        }

        // Keep transparency intact for PNG and WebP sources.
        imagealphablending($target, false);
        imagesavealpha($target, true);

        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $target;
    }

    private function encodeWebp(\GdImage $image): string
    {
        ob_start();
        $ok = imagewebp($image, null, self::QUALITY);
        $bytes = (string) ob_get_clean();

        if (!$ok || $bytes === '') {
            throw new RuntimeException('Could not convert that image. Please try another photo.');
        }

        return $bytes;
    }
}
