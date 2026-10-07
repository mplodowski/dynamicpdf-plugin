<?php
    $unchange = "$(this).trigger('unchange.oc.changeMonitor')";
    $confirmUnsaved = fn (string $message): string => e("var form = $(this).closest('[data-change-monitor]');"
        . " if (!form.hasClass('oc-data-changed')) return;"
        . ' oc.confirmPromise(' . json_encode($message) . ')'
        . ".then(() => oc.request(this, null, { beforeSendFunc: null, beforeUpdateFunc: () => { {$unchange} } }), () => {});"
        . ' return false;');
?>
<div>
    <?php if (! $formModel->exists): ?>
        <?= Ui::ajaxButton(
            label: __('Create'),
            handler: 'onSave',
            primary: true,
            hotkey: ['ctrl+s', 'cmd+s'],
            dataRequestBeforeUpdate: $unchange,
            dataRequestMessage: trans('backend::lang.form.creating')
        ) ?>

        <?= Ui::ajaxButton(
            label: trans('backend::lang.form.create_and_close'),
            handler: 'onSave',
            secondary: true,
            hotkey: ['ctrl+enter', 'cmd+enter'],
            dataBrowserRedirectBack: true,
            dataRequestData: 'close: true',
            dataRequestBeforeUpdate: $unchange,
            dataRequestMessage: trans('backend::lang.form.creating')
        ) ?>
    <?php else: ?>
        <?= Ui::ajaxButton(
            label: __('Save'),
            handler: 'onSave',
            primary: true,
            hotkey: ['ctrl+s', 'cmd+s'],
            dataRequestData: 'redirect: false',
            dataRequestBeforeUpdate: $unchange,
            dataRequestMessage: trans('backend::lang.form.saving')
        ) ?>

        <?= Ui::ajaxButton(
            label: trans('backend::lang.form.save_and_close'),
            handler: 'onSave',
            secondary: true,
            hotkey: ['ctrl+enter', 'cmd+enter'],
            dataBrowserRedirectBack: true,
            dataRequestData: 'close: true',
            dataRequestBeforeUpdate: $unchange,
            dataRequestMessage: trans('backend::lang.form.saving')
        ) ?>
    <?php endif ?>

    <?php if ($this->formCheckPermission('modelPreview')): ?>
        <?= Ui::popupButton(
            label: trans('renatio.dynamicpdf::lang.templates.preview_html'),
            handler: 'onPreviewUnsaved',
            requestData: ['mode' => 'html'],
            class: 'btn-info'
        ) ?>

        <?= Ui::popupButton(
            label: trans('renatio.dynamicpdf::lang.templates.preview_pdf'),
            handler: 'onPreviewUnsaved',
            requestData: ['mode' => 'pdf'],
            class: 'btn-info'
        ) ?>
    <?php endif ?>

    <?php if ($formModel->exists): ?>
        <?php if ($this->formCheckPermission('modelCreate')): ?>
            <?= Ui::ajaxButton(
                label: trans('renatio.dynamicpdf::lang.templates.duplicate'),
                handler: 'onDuplicate',
                class: 'btn-default',
                dataRequestBeforeSend: $confirmUnsaved(trans('renatio.dynamicpdf::lang.templates.duplicate_unsaved')),
                dataRequestMessage: trans('renatio.dynamicpdf::lang.templates.duplicating')
            ) ?>
        <?php endif ?>

        <?php $name = ['name' => $formModel->{$formModel->labelAttribute()}] ?>
        <?php $usedBy = $formModel instanceof Renatio\DynamicPDF\Models\Layout && ! $formModel->followsView() ? $formModel->usedByCount() : 0 ?>
        <?php if ($formModel->followsView()): ?>
            <?php if ($formModel->isCustomised() && $this->formCheckPermission('modelUpdate')): ?>
                <?= Ui::ajaxButton(
                    label: trans('backend::lang.form.reset_default'),
                    handler: 'onResetDefault',
                    class: 'btn-warning pull-right',
                    dataRequestConfirm: trans('renatio.dynamicpdf::lang.templates.reset_confirm', $name),
                    dataRequestBeforeUpdate: $unchange,
                    dataRequestMessage: trans('backend::lang.form.resetting')
                ) ?>
            <?php endif ?>
        <?php elseif ($usedBy > 0 && $this->formCheckPermission('modelDelete')): ?>
            <?php $usedByText = trans('renatio.dynamicpdf::lang.layout.delete_used_by', ['count' => $usedBy]) ?>
            <span class="pull-right" data-tooltip-text="<?= e($usedByText) ?>">
                <?= Ui::iconButton(
                    icon: 'oc-icon-delete',
                    danger: true,
                    disabled: true,
                    ariaLabel: trans('backend::lang.form.delete') . ' (' . $usedByText . ')'
                ) ?>
            </span>
        <?php elseif ($this->formCheckPermission('modelDelete')): ?>
            <?= Ui::iconButton(
                label: trans('backend::lang.form.delete'),
                icon: 'oc-icon-delete',
                handler: 'onDelete',
                danger: true,
                hotkey: ['shift+option+d'],
                class: 'pull-right',
                ariaLabel: trans('backend::lang.form.delete'),
                dataBrowserRedirectBack: true,
                dataRequestConfirm: trans('renatio.dynamicpdf::lang.templates.delete_confirm', $name),
                dataRequestBeforeUpdate: $unchange,
                dataRequestMessage: trans('backend::lang.form.deleting')
            ) ?>
        <?php endif ?>
    <?php endif ?>

    <span class="btn-text">
        <span class="button-separator"><?= __('or') ?></span>
        <?= Ui::ajaxButton(
            label: __('Cancel'),
            handler: 'onCancel',
            hotkey: ['shift+option+c'],
            href: 'javascript:;',
            class: 'btn-link p-0',
            dataBrowserRedirectBack: true,
            dataRequestData: 'close: true',
            dataRequestBeforeSend: $confirmUnsaved(trans('renatio.dynamicpdf::lang.templates.cancel_unsaved')),
            dataRequestMessage: trans('backend::lang.list.loading')
        ) ?>
    </span>
</div>
