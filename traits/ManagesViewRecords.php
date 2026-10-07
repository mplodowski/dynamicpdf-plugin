<?php

namespace Renatio\DynamicPDF\Traits;

use Backend\Facades\BackendMenu;
use Dompdf\Exception as DompdfException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use October\Rain\Exception\ApplicationException;
use October\Rain\Exception\ForbiddenException;
use October\Rain\Exception\ValidationException;
use October\Rain\Support\Facades\Flash;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFWrapper;
use Renatio\DynamicPDF\Classes\PreviewFonts;
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
        if ($this->getAjaxHandler() === null) {
            (new SyncTemplates)->handle();
        }
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
            $html = (new PreviewFonts)->inline($model->getHtmlAttribute());
        } catch (TwigError $e) {
            $html = '<p>' . e($this->previewFailedMessage($e)) . '</p>';
        }

        return response($html)->header('Content-Security-Policy', "sandbox; script-src 'none'; object-src 'none'");
    }

    /**
     * Rendering posted markup is as powerful as saving it, so the form's save permission is
     * required on top of the preview one.
     */
    public function onPreviewUnsaved(): string
    {
        $this->requireFormPermission('modelPreview');

        $widget = $this->formGetWidget() ?? throw new ForbiddenException;
        $model = $widget->model;

        if ((! $model instanceof Template && ! $model instanceof Layout) || ! in_array($this->action, ['create', 'update'], true)) {
            throw new ForbiddenException;
        }

        $this->requireFormPermission($model->exists ? 'modelUpdate' : 'modelCreate');

        $widget->setFormValues();

        if ($model instanceof Template) {
            $validator = Validator::make(['sample_data' => $model->sample_data], ['sample_data' => ['nullable', 'json']]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }

        if ($model instanceof Layout && ! $model->exists) {
            $model->setRelation('background_img', $model->background_img()->withDeferred($widget->getSessionKey())->first());
        }

        $pdf = post('mode') === 'pdf';

        try {
            $preview = $pdf
                ? base64_encode($this->loadPreviewPdf($model)->allowRemoteApplicationAssets()->output())
                : (new PreviewFonts)->inline($model->getHtmlAttribute());
        } catch (TwigError $e) {
            throw new ApplicationException($this->previewFailedMessage($e));
        } catch (DompdfException $e) {
            throw new ApplicationException(trans('renatio.dynamicpdf::lang.templates.preview_failed', ['message' => $e->getMessage()]));
        }

        return $this->makePartial('preview_popup', [
            'title' => trans($pdf ? 'renatio.dynamicpdf::lang.templates.preview_pdf' : 'renatio.dynamicpdf::lang.templates.preview_html'),
            'pdf' => $pdf ? $preview : null,
            'html' => $pdf ? null : $preview,
            'pageSize' => $this->previewPageSize($model),
        ]);
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
        $dompdf = app('dompdf');
        $options = $dompdf->getOptions();
        $template = $model instanceof Template ? $model : null;

        $points = $dompdf->setPaper(
            $template?->size ?: $options->getDefaultPaperSize(),
            $template?->orientation ?: $options->getDefaultPaperOrientation(),
        )->getPaperSize();

        return [(int) round($points[2] * 96 / 72), (int) round($points[3] * 96 / 72)];
    }

    protected function loadPreviewPdf(Layout|Template $model): PDFWrapper
    {
        $pdf = $model instanceof Template
            ? PDF::loadTemplateModel($model, $model->sampleData())
            : PDF::loadLayoutModel($model);

        return $pdf->setIsPhpEnabled(false);
    }
}
