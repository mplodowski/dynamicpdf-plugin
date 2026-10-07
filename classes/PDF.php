<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use RuntimeException;

/**
 * @method static PDFWrapper loadTemplate(string $code, array<string, mixed> $data = [], ?string $encoding = null, ?string $layout = null, ?string $locale = null)
 * @method static PDFWrapper loadLayout(string $code, array<string, mixed> $data = [], ?string $encoding = null, ?string $locale = null)
 * @method static string parseTemplate(Template $template, array<string, mixed> $data = [])
 * @method static string parseLayout(Layout $layout, array<string, mixed> $data = [])
 * @method static PDFWrapper pageNumbers(string $text = 'Page {PAGE_NUM} of {PAGE_COUNT}', string $position = 'bottom-center', float $size = 9, ?string $font = null, float $margin = 20, array<int, float> $color = [0, 0, 0])
 * @method static \System\Models\File toFile(string $filename = 'document.pdf', bool $public = true)
 * @method static PDFWrapper encrypt(string $password, string $ownerPassword = '', array<int, string> $permissions = [])
 * @method static PDFWrapper allowRemoteApplicationAssets()
 * @method static PDFWrapper allowSelfSignedCertificates()
 * @method static PDFWrapper loadHTML(string $string, ?string $encoding = null)
 * @method static PDFWrapper loadFile(string $file)
 * @method static PDFWrapper loadView(string $view, array<string, mixed> $data = [], array<string, mixed> $mergeData = [], ?string $encoding = null)
 * @method static PDFWrapper setPaper(string|float[] $paper, string $orientation = 'portrait')
 * @method static PDFWrapper setOption(array<string, mixed>|string $attribute, mixed $value = null)
 * @method static PDFWrapper setOptions(array<string, mixed> $options, bool $mergeWithDefaults = false)
 * @method static PDFWrapper addInfo(array<string, string> $info)
 * @method static PDFWrapper setWarnings(bool $warnings)
 */
class PDF extends PdfFacade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dynamicpdf';
    }

    public static function fake(): PDFFake
    {
        $app = static::getFacadeApplication() ?? throw new RuntimeException('Facade application has not been set.');

        $fake = new PDFFake($app->make('dompdf'), $app->make('config'), $app->make('files'), $app->make('view'));
        static::swap($fake);

        return $fake;
    }
}
