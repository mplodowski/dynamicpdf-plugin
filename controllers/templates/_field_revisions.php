<?php
    $revisions = $formModel->revisions()->orderByDesc('id')->get();
    $canRestore = $this->formCheckPermission('modelUpdate');
?>
<div role="group" aria-labelledby="<?= $field->getId('label') ?>">
    <span class="form-label d-block" id="<?= $field->getId('label') ?>"><?= e(trans('renatio.dynamicpdf::lang.revisions.label')) ?></span>
    <p class="form-text mt-0"><?= e(trans('renatio.dynamicpdf::lang.revisions.comment', ['limit' => Renatio\DynamicPDF\Models\Revision::LIMIT])) ?></p>

    <?php if ($revisions->isEmpty()): ?>
        <p class="text-muted"><?= e(trans('renatio.dynamicpdf::lang.revisions.empty')) ?></p>
    <?php else: ?>
        <table class="table data">
            <thead>
                <tr>
                    <th><?= e(trans('renatio.dynamicpdf::lang.revisions.date')) ?></th>
                    <th><?= e(trans('renatio.dynamicpdf::lang.revisions.author')) ?></th>
                    <?php if ($canRestore): ?>
                        <th><span class="visually-hidden"><?= e(trans('renatio.dynamicpdf::lang.templates.actions')) ?></span></th>
                    <?php endif ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revisions as $revision): ?>
                    <?php $date = $revision->createdAtLabel() ?>
                    <tr>
                        <td><?= e($date) ?></td>
                        <td><?= e($revision->user_name ?: '-') ?></td>
                        <?php if ($canRestore): ?>
                            <td class="text-end">
                                <?= Ui::ajaxButton(
                                    label: trans('renatio.dynamicpdf::lang.revisions.restore'),
                                    handler: 'onRestoreRevision',
                                    requestData: ['revision' => $revision->id],
                                    class: 'btn-default btn-sm',
                                    dataRequestConfirm: trans('renatio.dynamicpdf::lang.revisions.restore_confirm', ['date' => $date]),
                                    dataRequestBeforeUpdate: "$(this).trigger('unchange.oc.changeMonitor')",
                                    dataRequestMessage: trans('renatio.dynamicpdf::lang.revisions.restoring')
                                ) ?>
                            </td>
                        <?php endif ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</div>
