<?php

namespace Renatio\DynamicPDF\Classes;

use Composer\InstalledVersions;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
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
 * Both packages are only suggested, so the functions stay registered and explain what to install. Each package is
 * probed by a class of its supported major version. dompdf needs GD for PNG images, so without it the code is drawn
 * as SVG.
 */
class Codes
{
    public const PACKAGES = [
        'pdf_qr()' => ['package' => 'endroid/qr-code', 'class' => ErrorCorrectionLevel::class, 'major' => 5],
        'pdf_barcode()' => ['package' => 'picqer/php-barcode-generator', 'class' => BarcodeGeneratorPNG::class, 'major' => 3],
    ];

    public const QR_DEFAULTS = ['size' => 150, 'margin' => 0, 'color' => '#000000', 'background' => '#ffffff', 'error_correction' => 'low'];

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

    public function qrAvailable(): bool
    {
        return class_exists(static::PACKAGES['pdf_qr()']['class']);
    }

    public function barcodeAvailable(): bool
    {
        return class_exists(static::PACKAGES['pdf_barcode()']['class']);
    }

    /**
     * The options may replace the size argument, so pdf_qr(text, {color: '#1f2937'}) keeps the default size.
     */
    public function qr(mixed $text, mixed $size = 150, mixed $margin = 0, mixed $options = []): string
    {
        $function = 'pdf_qr()';
        $this->requirePackage($function);

        if (is_array($size)) {
            [$options, $size] = [$size, self::QR_DEFAULTS['size']];
        }

        $options = $this->qrOptions($options, ['size' => $size, 'margin' => $margin] + self::QR_DEFAULTS);

        $qrCode = new QrCode(
            data: $this->text($text, $function),
            errorCorrectionLevel: $options['error_correction'],
            size: $options['size'],
            margin: $options['margin'],
            foregroundColor: $options['color'],
            backgroundColor: $options['background'],
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

    /**
     * @param  array<string, mixed>  $defaults
     * @return array{size: int, margin: int, color: Color, background: Color, error_correction: ErrorCorrectionLevel}
     */
    protected function qrOptions(mixed $options, array $defaults): array
    {
        $function = 'pdf_qr()';

        if (! is_array($options)) {
            throw $this->error('invalid_argument', ['argument' => 'options', 'function' => $function]);
        }

        $unknown = array_diff(array_keys($options), array_keys(self::QR_DEFAULTS));

        if ($unknown !== []) {
            throw $this->error('unknown_option', [
                'option' => Str::limit((string) reset($unknown), 40),
                'function' => $function,
                'options' => implode(', ', array_keys(self::QR_DEFAULTS)),
            ]);
        }

        $options += $defaults;
        $level = ErrorCorrectionLevel::tryFrom(strtolower($this->text($options['error_correction'], $function, 'error_correction')));

        return [
            'size' => $this->integer($options['size'], 1, $function, 'size'),
            'margin' => $this->integer($options['margin'], 0, $function, 'margin'),
            'color' => $this->color($options['color'], $function, 'color'),
            'background' => $this->color($options['background'], $function, 'background'),
            'error_correction' => $level ?? throw $this->error('invalid_argument', ['argument' => 'error_correction', 'function' => $function]),
        ];
    }

    protected function color(mixed $value, string $function, string $argument): Color
    {
        $color = strtolower($this->text($value, $function, $argument));

        if ($color === 'transparent') {
            return new Color(255, 255, 255, 127);
        }

        if (! preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $color, $match)) {
            throw $this->error('invalid_argument', ['argument' => $argument, 'function' => $function]);
        }

        $hex = strlen($match[1]) === 3 ? preg_replace('/(.)/', '$1$1', $match[1]) : $match[1];

        return new Color(...array_map(fn (string $pair): int => (int) hexdec($pair), str_split((string) $hex, 2)));
    }

    protected function requirePackage(string $function): void
    {
        ['package' => $package, 'class' => $class, 'major' => $major] = static::PACKAGES[$function];

        if (class_exists($class)) {
            return;
        }

        if (InstalledVersions::isInstalled($package)) {
            throw $this->error('outdated_package', ['function' => $function, 'package' => $package, 'major' => $major]);
        }

        throw $this->error('missing_package', ['function' => $function, 'package' => $package]);
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
