<?php namespace Renatio\DynamicPDF\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use Twig;
use Flash;
use App;
use DOMPDF_Exception;
use Redirect;

/**
 * PDFLayout Model
 */
class PDFLayout extends Model
{

    /**
     * Traits
     */
    use Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'renatio_dynamicpdf_pdf_layouts';

    /**
     * @var array Validation Rules
     */
    public $rules = [
        'code' => 'required|unique:renatio_dynamicpdf_pdf_layouts',
        'name' => 'required',
    ];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['name', 'code', 'content_html', 'content_css'];

    /**
     * @var array
     */
    public $attachOne = [
        'background_img' => ['System\Models\File']
    ];

    /**
     * Render PDF Layout
     *
     * @param string $code Layout code
     * @param array $params Output parameters
     * @return mixed
     */
    public static function render($code, $params = [])
    {
        $self = new self;
        $html = $self->getHtmlForPDF($code);

        return $self->outputPDF($html, $params);
    }

    /**
     * Get HTML from layout
     *
     * @param $layout_code
     * @return mixed
     */
    private function getHtmlForPDF($layout_code)
    {
        $layout = $this->whereCode($layout_code)->firstOrFail();

        return $this->parsePDFLayout($layout);
    }

    /**
     * Parse PDF layout
     *
     * @param PDFLayout $layout
     * @return mixed
     */
    private function parsePDFLayout(PDFLayout $layout)
    {
        $html = Twig::parse($layout->content_html, [
            'background_img' => $layout->background_img ? $layout->background_img->getPath() : '',
            'css'            => $layout->content_css
        ]);

        return $html;
    }

    /**
     * Output pdf to browser
     *
     * @param string $html
     * @param array $params
     * @return mixed
     */
    private function outputPDF($html, $params = [])
    {
        try
        {
            $pdf = App::make('dompdf.wrapper');
            $pdf->loadHTML($html);
            $filename = isset($params['filename']) ? $params['filename'] : 'document.pdf';

            return $pdf->stream($filename, $params);

        } catch (DOMPDF_Exception $e)
        {
            Flash::error($e->getMessage());

            return Redirect::back();
        }
    }
}