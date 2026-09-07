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
 */
class PDF extends PdfFacade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dynamicpdf';
    }

    /**
     * Replace the wrapper with a recorder for the rest of the test, so no template is looked
     * up and no PDF is produced.
     */
    public static function fake(): PDFFake
    {
        $app = static::getFacadeApplication() ?? throw new RuntimeException('Facade application has not been set.');

        $fake = new PDFFake($app->make('dompdf'), $app->make('config'), $app->make('files'), $app->make('view'));
        $app->instance(static::getFacadeAccessor(), $fake);

        return $fake;
    }
}
