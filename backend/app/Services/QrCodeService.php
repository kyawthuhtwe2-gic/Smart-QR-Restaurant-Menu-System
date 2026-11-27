<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    private const MODULE_PIXELS = 12;

    private const QUIET_ZONE_MODULES = 4;

    /**
     * Vector QR for on-screen preview and printing.
     */
    public function svg(string $content, int $size = 300): string
    {
        return (string) QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($content);
    }

    /**
     * PNG QR rendered with GD from the encoder matrix (works without Imagick).
     */
    public function png(string $content): string
    {
        if (! function_exists('imagecreate')) {
            throw new RuntimeException('The GD extension is required to generate PNG QR codes.');
        }

        $matrix = Encoder::encode($content, ErrorCorrectionLevel::M(), 'UTF-8')->getMatrix();
        $modules = $matrix->getWidth();
        $pixels = ($modules + self::QUIET_ZONE_MODULES * 2) * self::MODULE_PIXELS;

        $image = imagecreate($pixels, $pixels);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $left = ($x + self::QUIET_ZONE_MODULES) * self::MODULE_PIXELS;
                $top = ($y + self::QUIET_ZONE_MODULES) * self::MODULE_PIXELS;
                imagefilledrectangle($image, $left, $top, $left + self::MODULE_PIXELS - 1, $top + self::MODULE_PIXELS - 1, $black);
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }
}
