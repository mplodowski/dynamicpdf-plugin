<?php namespace Renatio\DynamicPDF\Models;

use App;
use DOMPDF_Exception;
use Redirect;
use Model;
use October\Rain\Database\Traits\Validation;
use Flash;
use Twig;

/**
 * PDFTemplate Model
 */
class PDFTemplate extends Model
{

    /**
     * Traits
     */
    use Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'renatio_dynamicpdf_pdf_templates';

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'layout' => ['Renatio\DynamicPDF\Models\PDFLayout']
    ];

    /**
     * @var array Validation Rules
     */
    public $rules = [
        'code'  => 'required|unique:renatio_dynamicpdf_pdf_templates',
        'title' => 'required',
    ];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['title', 'description', 'code', 'content_html', 'layout'];

    /**
     * Render PDF Template
     *
     * @param string $code Template code
     * @param array $data Template data
     * @param array $params Output parameters
     * @return mixed
     */
    public static function render($code, $data = [], $params = [])
    {
        $self = new self;
        $html = $self->getHtmlForPDF($code, $data);

        return $self->outputPDF($html, $params);
    }

    /**
     * Get HTML from template
     *
     * @param $template_code
     * @param $data
     * @return mixed
     */
    private function getHtmlForPDF($template_code, $data)
    {
        $template = $this->whereCode($template_code)->firstOrFail();

        return $this->parsePDFTemplate($template, $data);
    }

    /**
     * Parse PDF template
     *
     * @param PDFTemplate $template
     * @param $data
     * @return mixed
     */
    private function parsePDFTemplate(PDFTemplate $template, $data)
    {
        $html = Twig::parse($template->content_html, $data);

        if ($template->layout)
        {
            $html = Twig::parse($template->layout->content_html, [
                'content_html'   => $html,
                'background_img' => $template->layout->background_img ? $template->layout->background_img->getPath() : '',
                'css'            => $template->layout->content_css
            ]);
        }

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