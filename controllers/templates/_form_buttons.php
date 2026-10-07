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

        <?php if ($formModel->followsView() && $this->formCheckPermission('modelUpdate')): ?>
            <?= Ui::ajaxButton(
                label: trans('backend::lang.form.reset_default'),
                handler: 'onResetDefault',
                class: 'btn-warning pull-right',
                dataRequestConfirm: trans('backend::lang.form.action_confirm'),
                dataRequestBeforeUpdate: $unchange,
                dataRequestMessage: trans('backend::lang.form.resetting')
            ) ?>
        <?php elseif (! $formModel->followsView() && $this->formCheckPermission('modelDelete')): ?>
            <?= Ui::iconButton(
                label: trans('backend::lang.form.delete'),
                icon: 'oc-icon-delete',
                handler: 'onDelete',
                danger: true,
                hotkey: ['shift+option+d'],
                class: 'pull-right',
                ariaLabel: trans('backend::lang.form.delete'),
                dataBrowserRedirectBack: true,
                dataRequestConfirm: trans('backend::lang.form.action_confirm'),
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
