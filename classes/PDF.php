<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

/**
 * @method static PDFWrapper loadTemplate(string $code, array<string, mixed> $data = [], ?string $encoding = null, ?string $layout = null, ?string $locale = null)
 * @method static PDFWrapper loadLayout(string $code, array<string, mixed> $data = [], ?string $encoding = null, ?string $locale = null)
 * @method static string parseTemplate(Template $template, array<string, mixed> $data = [])
 * @method static string parseLayout(Layout $layout, array<string, mixed> $data = [])
 * @method static PDFWrapper pageNumbers(string $text = 'Page {PAGE_NUM} of {PAGE_COUNT}', string $position = 'bottom-center', float $size = 9, ?string $font = null, float $margin = 20, array<int, float> $color = [0, 0, 0])
 * @method static \System\Models\File toFile(string $filename = 'document.pdf')
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
}
