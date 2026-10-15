<?php
    $callout = Ui::callout();
    $text = Markdown::parse(e($field->getConfig('text')));

    if ($field->getConfig('mode') !== 'warning') {
        $callout->tip()->icon('ph ph-link-simple')->commentHtml($text);
    } else {
        $reset = $this->formCheckPermission('modelUpdate') ? Ui::ajaxButton(
            label: trans('backend::lang.form.reset_default'),
            handler: 'onResetDefault',
            class: 'btn-sm btn-warning',
            dataRequestConfirm: trans('renatio.dynamicpdf::lang.templates.reset_confirm', ['name' => $formModel->{$formModel->labelAttribute()}]),
            dataRequestBeforeUpdate: "$(this).trigger('unchange.oc.changeMonitor')",
            dataRequestMessage: trans('backend::lang.form.resetting')
        ) : '';

        $callout->warning()->icon('ph ph-link-simple-break')->action($reset)->commentHtml($text);
    }
?>
<?= $callout ?>
