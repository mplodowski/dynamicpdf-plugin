<?php

namespace Renatio\DynamicPDF\Traits;

use Backend\Facades\BackendMenu;
use Dompdf\Adapter\CPDF;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Facades\Flash;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFWrapper;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\SettingsManager;
use Twig\Error\Error as TwigError;

/**
 * @mixin \Backend\Classes\Controller
 */
trait ManagesViewRecords
{
    use ChecksFormPermissions;
    use RendersPreviewErrors;

    protected function setSettingsContext(): void
    {
        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Renatio.DynamicPDF', 'templates');
    }

    public function beforeDisplay(): void
    {
        (new SyncTemplates)->handle();
    }

    public function previewpdf(int|string $id): ?Response
    {
        $this->requireFormPermission('modelPreview');

        $this->pageTitle = e(trans('renatio.dynamicpdf::lang.templates.preview_pdf'));

        try {
            $model = $this->formFindModelObject($id);
        } catch (ApplicationException $e) {
            $this->handleError($e);

            return null;
        }

        try {
            $pdf = $this->loadPreviewPdf($model)
                ->setLogOutputFile(config('app.debug') ? storage_path('temp/log.htm') : '')
                ->allowRemoteApplicationAssets();

            $pdf->render();
        } catch (TwigError $e) {
            $this->handleError(new ApplicationException($this->previewFailedMessage($e)));

            return null;
        }

        $title = (string) $model->{$model->labelAttribute()};

        return $pdf->addInfo(['Title' => $title])->stream(Str::slug($title) . '.pdf');
    }

    public function html(int|string $id): Response
    {
        $this->requireFormPermission('modelPreview');

        $model = $this->formFindModelObject($id);

        try {
            $html = $model->getHtmlAttribute();
        } catch (TwigError $e) {
            $html = '<p>' . e($this->previewFailedMessage($e)) . '</p>';
        }

        return response($html)->header('Content-Security-Policy', "sandbox; script-src 'none'; object-src 'none'");
    }

    public function update_onDuplicate(int|string $recordId): RedirectResponse
    {
        $this->requireFormPermission('modelCreate');

        $copy = $this->formFindModelObject($recordId)->duplicate();

        Flash::success(e(trans('renatio.dynamicpdf::lang.templates.duplicate_success')));

        return redirect()->to($this->actionUrl('update', (string) $copy->id));
    }

    public function update_onResetDefault(int|string $recordId): RedirectResponse
    {
        $this->requireFormPermission('modelUpdate');

        $this->formFindModelObject($recordId)->resetToView();

        Flash::success(e(trans('renatio.dynamicpdf::lang.templates.reset_success')));

        return redirect()->refresh();
    }

    public function update_onDelete(int|string|null $recordId = null): mixed
    {
        $this->requireFormPermission('modelDelete');

        if ($this->formFindModelObject($recordId)->followsView()) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.templates.delete_view_refused')));
        }

        return $this->asExtension('FormController')->update_onDelete($recordId);
    }

    /**
     * CSS pixels of the page the PDF preview renders on (dompdf sizes are in points, 72 per inch).
     *
     * @return array{int, int}
     */
    protected function previewPageSize(Layout|Template $model): array
    {
        $options = new Options(app('dompdf.options'));
        $template = $model instanceof Template ? $model : null;
        $size = $template?->size ?: $options->getDefaultPaperSize();
        $orientation = $template?->orientation ?: $options->getDefaultPaperOrientation();
        $points = is_array($size) ? $size : (CPDF::$PAPER_SIZES[strtolower((string) $size)] ?? CPDF::$PAPER_SIZES['a4']);

        $width = (int) round($points[2] * 96 / 72);
        $height = (int) round($points[3] * 96 / 72);

        return strtolower($orientation) === 'landscape' ? [$height, $width] : [$width, $height];
    }

    protected function loadPreviewPdf(Layout|Template $model): PDFWrapper
    {
        return $model instanceof Template
            ? PDF::loadTemplate($model->code, $model->sampleData())
            : PDF::loadLayout($model->code);
    }
}
