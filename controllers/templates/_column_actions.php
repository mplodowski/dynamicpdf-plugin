<?php
    $definition = $this->definitionOf($record);
    $requestData = "id: {$record->id}, definition: '{$definition}'";
    $permission = $this->permission($definition);
    $name = ['name' => $record->{$record->labelAttribute()}];
    $usedBy = $definition === 'layouts' ? $record->usedByCount() : 0;
?>
<div class="d-inline-flex gap-2 text-nowrap">
    <?php if (BackendAuth::userHasAccess($permission . '.preview')): ?>
        <a href="<?= Backend::url("renatio/dynamicpdf/{$definition}/previewpdf/{$record->id}") ?>"
           target="_blank"
           rel="opener"
           class="btn btn-sm btn-primary"
           data-tooltip-text="<?= e(trans('renatio.dynamicpdf::lang.templates.preview_pdf')) ?>"
           aria-label="<?= e(trans('renatio.dynamicpdf::lang.templates.preview_pdf')) ?>"><i class="octo-icon-file-pdf-o icon-lg m-0" aria-hidden="true"></i></a>
    <?php endif ?>

    <?php if (BackendAuth::userHasAccess($permission . '.create')): ?>
        <button type="button"
                class="btn btn-sm btn-default"
                data-request="onDuplicateRecord"
                data-request-data="<?= $requestData ?>"
                data-request-confirm="<?= e(trans('backend::lang.form.action_confirm')) ?>"
                data-load-indicator="<?= e(trans('renatio.dynamicpdf::lang.templates.duplicating')) ?>"
                data-tooltip-text="<?= e(trans('renatio.dynamicpdf::lang.templates.duplicate')) ?>"
                aria-label="<?= e(trans('renatio.dynamicpdf::lang.templates.duplicate')) ?>"><i class="octo-icon-copy icon-lg m-0" aria-hidden="true"></i></button>
    <?php endif ?>

    <?php if ($record->followsView()): ?>
        <?php if ($record->isCustomised() && BackendAuth::userHasAccess($permission . '.update')): ?>
            <button type="button"
                    class="btn btn-sm btn-warning"
                    data-request="onResetRecord"
                    data-request-data="<?= $requestData ?>"
                    data-request-confirm="<?= e(trans('renatio.dynamicpdf::lang.templates.reset_confirm', $name)) ?>"
                    data-load-indicator="<?= e(trans('backend::lang.form.resetting')) ?>"
                    data-tooltip-text="<?= e(trans('backend::lang.form.reset_default')) ?>"
                    aria-label="<?= e(trans('backend::lang.form.reset_default')) ?>"><i class="octo-icon-refresh icon-lg m-0" aria-hidden="true"></i></button>
        <?php endif ?>
    <?php elseif ($usedBy > 0 && BackendAuth::userHasAccess($permission . '.delete')): ?>
        <?php $usedByText = trans('renatio.dynamicpdf::lang.layout.delete_used_by', ['count' => $usedBy]) ?>
        <span data-tooltip-text="<?= e($usedByText) ?>">
            <button type="button"
                    class="btn btn-sm btn-danger"
                    disabled
                    aria-label="<?= e(trans('backend::lang.form.delete') . ' (' . $usedByText . ')') ?>"><i class="octo-icon-delete icon-lg m-0" aria-hidden="true"></i></button>
        </span>
    <?php elseif (BackendAuth::userHasAccess($permission . '.delete')): ?>
        <button type="button"
                class="btn btn-sm btn-danger"
                data-request="onDeleteRecord"
                data-request-data="<?= $requestData ?>"
                data-request-confirm="<?= e(trans('renatio.dynamicpdf::lang.templates.delete_confirm', $name)) ?>"
                data-load-indicator="<?= e(trans('backend::lang.form.deleting')) ?>"
                data-tooltip-text="<?= e(trans('backend::lang.form.delete')) ?>"
                aria-label="<?= e(trans('backend::lang.form.delete')) ?>"><i class="octo-icon-delete icon-lg m-0" aria-hidden="true"></i></button>
    <?php endif ?>
</div>
