<?php

namespace Renatio\DynamicPDF\Traits;

use Backend\Facades\Backend;
use Backend\Facades\BackendMenu;
use DateTimeInterface;
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
use Renatio\DynamicPDF\Classes\RevisionHistory;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Classes\TextDiff;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Revision;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\SettingsManager;
use Twig\Error\Error as TwigError;

/**
 * @mixin \Backend\Classes\Controller
 */
trait ManagesViewRecords
{
    protected const CSS_PIXELS_PER_POINT = 96 / 72;

    /** @var array<string, mixed>|null */
    protected ?array $revisionSnapshot = null;

    protected ?DateTimeInterface $revisionSnapshotAt = null;

    protected bool $detachedBySave = false;

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

    public function formBeforeSave(Layout|Template $model): void
    {
        if (! $model->exists) {
            $model->markAsCustomised();
        } elseif (! $model->isCustomised() && $this->postedViewFieldChanged($model)) {
            $model->markAsCustomised();
            $this->detachedBySave = $model->hasView();
        }
    }

    public function formBeforeUpdate(Layout|Template $model): void
    {
        $this->revisionSnapshot = $model->revisionSnapshot();
        $this->revisionSnapshotAt = $model->updated_at ?? $model->created_at;
    }

    public function formAfterUpdate(Layout|Template $model): void
    {
        if ($this->revisionSnapshot !== null) {
            $model->recordRevision($this->revisionSnapshot, $this->revisionSnapshotAt);
        }
    }

    /**
     * The form data reaches the model only after formBeforeSave, so the posted values are compared.
     */
    protected function postedViewFieldChanged(Layout|Template $model): bool
    {
        $fields = $model::VIEW_FIELDS;
        $widget = $this->formGetWidget();

        if ($widget === null) {
            return false;
        }

        $posted = (array) $widget->getSaveData();

        if ($model->shouldTranslate()) {
            $fields = array_diff_key($fields, array_flip($model->getTranslatableAttributes()));
        }

        foreach ($fields as $field => $attribute) {
            if (array_key_exists($field, $posted) && $model::normalizeViewValue($posted[$field]) !== $model::normalizeViewValue($model->getAttribute($attribute))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed the redirect, or the refreshed history when the form stays open
     */
    public function update_onSave(int|string|null $recordId = null, ?string $context = null): mixed
    {
        $redirect = $this->asExtension('FormController')->update_onSave($recordId, $context);

        if ($redirect !== null || $this->detachedBySave) {
            return $redirect ?? redirect()->refresh();
        }

        return ['#dynamicpdf-revisions' => $this->makePartial('revisions', ['record' => $this->formGetModel()])];
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

    public function update_onRestoreRevision(int|string $recordId): RedirectResponse
    {
        $this->requireFormPermission('modelUpdate');

        $model = $this->formFindModelObject($recordId);
        $revision = $model->revisions()->find((int) post('revision'));

        if (! $revision instanceof Revision) {
            throw new ApplicationException(trans('renatio.dynamicpdf::lang.revisions.not_found'));
        }

        if ($model->restoreRevision($revision)) {
            Flash::success(trans('renatio.dynamicpdf::lang.revisions.restore_success'));
        }

        return redirect()->refresh();
    }

    public function update_onRevisionChanges(int|string $recordId): string
    {
        $model = $this->formFindModelObject($recordId);
        $revision = $model->revisions()->find((int) post('revision'));

        if (! $revision instanceof Revision) {
            throw new ApplicationException(trans('renatio.dynamicpdf::lang.revisions.not_found'));
        }

        $changes = (new RevisionHistory($model, $this->formGetWidget()?->getFields() ?? []))->changesOf($revision);
        $diff = new TextDiff;

        return $this->makePartial('revision_changes', [
            'revision' => $revision,
            'changes' => array_map(fn (array $change) => $change + ['hunks' => $diff->hunks($change['old'], $change['new'])], $changes ?? []),
            'isOldest' => $changes === null,
        ]);
    }

    protected function resetToView(Layout|Template $model): void
    {
        if (! $model->hasView()) {
            throw new ApplicationException(trans('renatio.dynamicpdf::lang.templates.reset_view_only'));
        }

        $previous = $model->revisionSnapshot();
        $previousAt = $model->updated_at ?? $model->created_at;

        $model->resetToView();
        $model->recordRevision($previous, $previousAt, Revision::EVENT_RESET);
    }

    public function update_onDelete(int|string|null $recordId = null): mixed
    {
        $this->requireFormPermission('modelDelete');

        if ($this->formFindModelObject($recordId)->hasView()) {
            throw new ApplicationException(trans('renatio.dynamicpdf::lang.templates.delete_view_refused'));
        }

        return $this->asExtension('FormController')->update_onDelete($recordId);
    }

    protected function requireFormPermission(string $permission): void
    {
        if (! $this->formCheckPermission($permission)) {
            throw new ForbiddenException;
        }
    }

    protected function previewFailedMessage(TwigError $e): string
    {
        if ($e->getPrevious() !== null) {
            report($e);
        }

        return trans('renatio.dynamicpdf::lang.templates.preview_failed', ['message' => $e->getMessage()]);
    }

    /**
     * @return array{int, int}
     */
    protected function previewPageSize(Layout|Template $model): array
    {
        $dompdf = app('dompdf');
        $paper = $model instanceof Template ? $model->paper($dompdf->getOptions()) : Template::defaultPaper($dompdf->getOptions());

        $points = $dompdf->setPaper(...$paper)->getPaperSize();

        return [(int) round($points[2] * self::CSS_PIXELS_PER_POINT), (int) round($points[3] * self::CSS_PIXELS_PER_POINT)];
    }

    /**
     * Dompdf sizes the root element to the page and a browser to the content, which squeezes a body
     * background sized 100% 100% into the content height.
     */
    protected function previewHtml(Layout|Template $model): string
    {
        $html = (new PreviewFonts)->inline($model->html);

        return preg_replace('~<head\b[^>]*>~i', '$0<style>html{min-height:100%}</style>', $html, 1) ?? $html;
    }

    protected function loadPreviewPdf(Layout|Template $model): PDFWrapper
    {
        $pdf = $model instanceof Template
            ? PDF::loadTemplateModel($model, $model->sampleData())
            : PDF::loadLayoutModel($model);

        return $pdf->setIsPhpEnabled(false);
    }
}
