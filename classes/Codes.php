<?php

namespace Renatio\DynamicPDF\Classes;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Str;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Stringable;
use Throwable;
use Twig\Error\RuntimeError;

/**
 * Both packages are only suggested, so the functions stay registered and explain what to install.
 * dompdf needs GD for PNG images, so without it the code is drawn as SVG.
 */
class Codes
{
    public const PACKAGES = [
        'pdf_qr()' => ['package' => 'endroid/qr-code', 'class' => QrCode::class, 'major' => 5],
        'pdf_barcode()' => ['package' => 'picqer/php-barcode-generator', 'class' => BarcodeGeneratorPNG::class, 'major' => 3],
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
        $function = 'pdf_qr()';
        $this->requirePackage($function);

        $qrCode = new QrCode(
            data: $this->text($text, $function),
            size: $this->integer($size, 1, $function, 'size'),
            margin: $this->integer($margin, 0, $function, 'margin'),
        );

        try {
            return (extension_loaded('gd') ? new PngWriter : new SvgWriter)->write($qrCode)->getDataUri();
        } catch (Throwable $e) {
            throw $this->error('qr_failed', ['reason' => $e->getMessage()]);
        }
    }

    public function barcode(mixed $text, mixed $type = self::TYPE_CODE_128, mixed $widthFactor = 2, mixed $height = 50): string
    {
        $function = 'pdf_barcode()';
        $this->requirePackage($function);

        $text = $this->text($text, $function);
        $type = strtoupper($this->text($type, $function, 'type'));
        $widthFactor = $this->integer($widthFactor, 1, $function, 'widthFactor');
        $height = $this->integer($height, 1, $function, 'height');

        if (! in_array($type, self::TYPES, true)) {
            throw $this->error('barcode_type', ['type' => Str::limit($type, 40), 'types' => implode(', ', self::TYPES)]);
        }

        if (! mb_check_encoding($text, 'ASCII')) {
            throw $this->invalidBarcode($text, $type, trans('renatio.dynamicpdf::lang.codes.reason.character'));
        }

        $png = extension_loaded('gd');

        try {
            if ($png) {
                $generator = new BarcodeGeneratorPNG;
                $generator->useGd();
                $image = $generator->getBarcode($text, $type, $widthFactor, $height);
            } else {
                $image = (new BarcodeGeneratorSVG)->getBarcode($text, $type, $widthFactor, $height);
            }
        } catch (Throwable $e) {
            $reason = self::REASONS[class_basename($e)] ?? null;

            throw $this->invalidBarcode($text, $type, $reason ? trans("renatio.dynamicpdf::lang.codes.reason.{$reason}") : $e->getMessage());
        }

        return 'data:image/' . ($png ? 'png' : 'svg+xml') . ';base64,' . base64_encode($image);
    }

    protected function requirePackage(string $function): void
    {
        if (! class_exists(self::PACKAGES[$function]['class'])) {
            throw $this->error('missing_package', ['function' => $function, 'package' => self::PACKAGES[$function]['package']]);
        }
    }

    protected function text(mixed $value, string $function, string $argument = 'text'): string
    {
        if (! is_scalar($value) && ! $value instanceof Stringable) {
            throw $this->error('invalid_argument', ['argument' => $argument, 'function' => $function]);
        }

        return (string) $value;
    }

    protected function integer(mixed $value, int $min, string $function, string $argument): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min]]);

        if ($integer === false || is_bool($value)) {
            throw $this->error('invalid_argument', ['argument' => $argument, 'function' => $function]);
        }

        return $integer;
    }

    protected function invalidBarcode(string $text, string $type, string $reason): RuntimeError
    {
        return $this->error('barcode_failed', ['text' => Str::limit($text, 40), 'type' => $type, 'reason' => $reason]);
    }

    /**
     * @param  array<string, string>  $replace
     */
    protected function error(string $key, array $replace): RuntimeError
    {
        return new RuntimeError(trans("renatio.dynamicpdf::lang.codes.{$key}", $replace));
    }
}
