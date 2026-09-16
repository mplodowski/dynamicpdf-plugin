<?php
    $definition = $record instanceof Renatio\DynamicPDF\Models\Layout ? 'layouts' : 'templates';
    $requestData = "id: {$record->id}, definition: '{$definition}'";
    $permission = "renatio.dynamicpdf.manage_{$definition}";
?>
<div class="d-inline-flex gap-2 text-nowrap">
    <?php if (BackendAuth::userHasAccess($permission . '.preview')): ?>
        <a href="<?= Backend::url("renatio/dynamicpdf/{$definition}/previewpdf/{$record->id}") ?>"
           target="_blank"
           class="btn btn-sm btn-primary"
           data-tooltip-text="<?= e(trans('renatio.dynamicpdf::lang.templates.preview_pdf')) ?>"><i class="octo-icon-file-pdf-o icon-lg m-0"></i></a>
    <?php endif ?>

    <?php if (BackendAuth::userHasAccess($permission . '.create')): ?>
        <button type="button"
                class="btn btn-sm btn-default"
                data-request="onDuplicateRecord"
                data-request-data="<?= $requestData ?>"
                data-request-confirm="<?= e(trans('backend::lang.form.action_confirm')) ?>"
                data-load-indicator="<?= e(trans('renatio.dynamicpdf::lang.templates.duplicating')) ?>"
                data-tooltip-text="<?= e(trans('renatio.dynamicpdf::lang.templates.duplicate')) ?>"><i class="octo-icon-copy icon-lg m-0"></i></button>
    <?php endif ?>

    <?php if ($record->followsView() && BackendAuth::userHasAccess($permission . '.update')): ?>
        <button type="button"
                class="btn btn-sm btn-danger"
                data-request="onResetRecord"
                data-request-data="<?= $requestData ?>"
                data-request-confirm="<?= e(trans('backend::lang.form.action_confirm')) ?>"
                data-load-indicator="<?= e(trans('backend::lang.form.resetting')) ?>"
                data-tooltip-text="<?= e(trans('backend::lang.form.reset_default')) ?>"><i class="octo-icon-refresh icon-lg m-0"></i></button>
    <?php elseif (! $record->followsView() && BackendAuth::userHasAccess($permission . '.delete')): ?>
        <button type="button"
                class="btn btn-sm btn-danger"
                data-request="onDeleteRecord"
                data-request-data="<?= $requestData ?>"
                data-request-confirm="<?= e(trans('backend::lang.form.action_confirm')) ?>"
                data-load-indicator="<?= e(trans('backend::lang.form.deleting')) ?>"
                data-tooltip-text="<?= e(trans('backend::lang.form.delete')) ?>"><i class="octo-icon-delete icon-lg m-0"></i></button>
    <?php endif ?>
</div>
