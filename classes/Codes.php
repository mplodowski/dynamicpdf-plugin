<?php

namespace Renatio\DynamicPDF\Classes;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Exception;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Twig\Error\RuntimeError;

/**
 * Both packages are only suggested, so the functions stay registered and explain what to install.
 * Without an image extension the code is drawn as SVG, which dompdf renders without one.
 */
class Codes
{
    public const PACKAGES = [
        'pdf_qr()' => 'endroid/qr-code',
        'pdf_barcode()' => 'picqer/php-barcode-generator',
    ];

    public const TYPE_EAN_13 = 'EAN13';

    public const TYPE_EAN_8 = 'EAN8';

    public const TYPE_UPC_A = 'UPCA';

    public const TYPE_CODE_128 = 'C128';

    public const TYPE_CODE_39 = 'C39';

    public const TYPES = [self::TYPE_EAN_13, self::TYPE_EAN_8, self::TYPE_UPC_A, self::TYPE_CODE_128, self::TYPE_CODE_39];

    protected const REASONS = [
        'InvalidCheckDigitException' => 'check_digit',
        'InvalidLengthException' => 'length',
        'InvalidCharacterException' => 'character',
        'InvalidFormatException' => 'format',
    ];

    public function qr(mixed $text, mixed $size = 150, mixed $margin = 0): string
    {
        $this->requirePackage('pdf_qr()', QrCode::class);

        $writer = extension_loaded('gd') ? new PngWriter : new SvgWriter;

        try {
            return $writer->write(new QrCode(data: (string) $text, size: (int) $size, margin: (int) $margin))->getDataUri();
        } catch (Exception $e) {
            throw $this->error('qr_failed', ['reason' => $e->getMessage()]);
        }
    }

    public function barcode(mixed $text, mixed $type = self::TYPE_CODE_128, mixed $widthFactor = 2, mixed $height = 50): string
    {
        $this->requirePackage('pdf_barcode()', BarcodeGeneratorPNG::class);

        $type = strtoupper((string) $type);

        if (! in_array($type, self::TYPES, true)) {
            throw $this->error('barcode_type', ['type' => $type, 'types' => implode(', ', self::TYPES)]);
        }

        $text = (string) $text;

        if (! mb_check_encoding($text, 'ASCII')) {
            throw $this->invalidBarcode($text, $type, trans('renatio.dynamicpdf::lang.codes.reason.character'));
        }

        $png = extension_loaded('imagick') || extension_loaded('gd');

        try {
            $image = $png
                ? (new BarcodeGeneratorPNG)->getBarcode($text, $type, (int) $widthFactor, (int) $height)
                : (new BarcodeGeneratorSVG)->getBarcode($text, $type, (int) $widthFactor, (int) $height);
        } catch (Exception $e) {
            $reason = self::REASONS[class_basename($e)] ?? null;

            throw $this->invalidBarcode($text, $type, $reason ? trans("renatio.dynamicpdf::lang.codes.reason.{$reason}") : $e->getMessage());
        }

        return 'data:image/' . ($png ? 'png' : 'svg+xml') . ';base64,' . base64_encode($image);
    }

    protected function requirePackage(string $function, string $class): void
    {
        if (! class_exists($class)) {
            throw $this->error('missing_package', ['function' => $function, 'package' => self::PACKAGES[$function]]);
        }
    }

    protected function invalidBarcode(string $text, string $type, string $reason): RuntimeError
    {
        return $this->error('barcode_failed', ['text' => $text, 'type' => $type, 'reason' => $reason]);
    }

    /**
     * @param  array<string, string>  $replace
     */
    protected function error(string $key, array $replace): RuntimeError
    {
        return new RuntimeError(trans("renatio.dynamicpdf::lang.codes.{$key}", $replace));
    }
}
