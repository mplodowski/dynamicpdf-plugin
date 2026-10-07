<?php

namespace Renatio\DynamicPDF\Traits;

use Backend\Facades\Backend;
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

    protected const CSS_PIXELS_PER_INCH = 96;

    protected const POINTS_PER_INCH = 72;

    protected function setSettingsContext(): void
    {
        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Renatio.DynamicPDF', 'templates');
    }

    public function beforeDisplay(): void
    {
        if ($this->getAjaxHandler() === null && in_array($this->action, ['index', 'create', 'update'], true)) {
            (new SyncTemplates)->handle();
        }
    }

    public function update(int|string|null $recordId = null, ?string $context = null): mixed
    {
        $response = $this->asExtension('FormController')->update($recordId, $context);
        $this->appendRecordNameToTitle();

        return $response;
    }

    public function preview(int|string|null $recordId = null, ?string $context = null): mixed
    {
        $response = $this->asExtension('FormController')->preview($recordId, $context);
        $this->appendRecordNameToTitle();

        return $response;
    }

    protected function listUrl(): string
    {
        return Backend::url($this->asExtension('FormController')->getConfig('defaultRedirect'));
    }

    protected function listLabel(string $key): string
    {
        return trans('renatio.dynamicpdf::lang.' . strtolower(class_basename(static::class)) . ".{$key}");
    }

    protected function appendRecordNameToTitle(): void
    {
        $model = $this->formGetModel();

        if ($model instanceof Template || $model instanceof Layout) {
            $this->pageTitle .= ': ' . $model->{$model->labelAttribute()};
        }
    }

    public function previewpdf(int|string|null $id = null): ?Response
    {
        $this->requireFormPermission('modelPreview');

        $this->pageTitle = trans('renatio.dynamicpdf::lang.templates.preview_pdf');

        try {
            $model = $this->formFindModelObject((string) $id);
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

    public function html(int|string|null $id = null): Response
    {
        $this->requireFormPermission('modelPreview');

        try {
            $model = $this->formFindModelObject((string) $id);
        } catch (ApplicationException $e) {
            return response('<p>' . e($e->getMessage()) . '</p>', 404);
        }

        try {
            $html = $this->previewHtml($model);
        } catch (TwigError $e) {
            $html = '<p>' . e($this->previewFailedMessage($e)) . '</p>';
        } catch (DompdfException $e) {
            $html = '<p>' . e(trans('renatio.dynamicpdf::lang.templates.preview_failed', ['message' => $e->getMessage()])) . '</p>';
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
                : $this->previewHtml($model);
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

        Flash::success(trans('renatio.dynamicpdf::lang.templates.duplicate_success'));

        return redirect()->to($this->actionUrl('update', (string) $copy->id));
    }

    public function update_onResetDefault(int|string $recordId): RedirectResponse
    {
        $this->requireFormPermission('modelUpdate');

        $this->resetToView($this->formFindModelObject($recordId));

        Flash::success(trans('renatio.dynamicpdf::lang.templates.reset_success'));

        return redirect()->refresh();
    }

    protected function resetToView(Layout|Template $model): void
    {
        if (! $model->followsView()) {
            throw new ApplicationException(trans('renatio.dynamicpdf::lang.templates.reset_view_only'));
        }

        $model->resetToView();
    }

    public function update_onDelete(int|string|null $recordId = null): mixed
    {
        $this->requireFormPermission('modelDelete');

        if ($this->formFindModelObject($recordId)->followsView()) {
            throw new ApplicationException(trans('renatio.dynamicpdf::lang.templates.delete_view_refused'));
        }

        return $this->asExtension('FormController')->update_onDelete($recordId);
    }

    /**
     * @return array{int, int}
     */
    protected function previewPageSize(Layout|Template $model): array
    {
        $dompdf = app('dompdf');
        $paper = $model instanceof Template ? $model->paper($dompdf->getOptions()) : Template::defaultPaper($dompdf->getOptions());

        $points = $dompdf->setPaper(...$paper)->getPaperSize();

        $toCssPixels = fn (float $points): int => (int) round($points * self::CSS_PIXELS_PER_INCH / self::POINTS_PER_INCH);

        return [$toCssPixels($points[2]), $toCssPixels($points[3])];
    }

    protected function previewHtml(Layout|Template $model): string
    {
        return (new PreviewFonts)->inline($model->html);
    }

    protected function loadPreviewPdf(Layout|Template $model): PDFWrapper
    {
        $pdf = $model instanceof Template
            ? PDF::loadTemplateModel($model, $model->sampleData())
            : PDF::loadLayoutModel($model);

        return $pdf->setIsPhpEnabled(false);
    }
}
