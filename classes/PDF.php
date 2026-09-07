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
