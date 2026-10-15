<?php
    $rows = (new Renatio\DynamicPDF\Classes\RevisionHistory($record, $this->formGetWidget()->getFields()))->rows();
    $canRestore = $this->formCheckPermission('modelUpdate');
    $currentId = $rows ? $record->currentRevisionId() : null;
    $snapshot = $rows && $canRestore ? $record->revisionSnapshot() : [];
?>
<?php if (! $rows): ?>
    <div class="dynamicpdf-revisions-empty">
        <i class="ph ph-clock-counter-clockwise" aria-hidden="true"></i>
        <p class="mb-0"><?= e(trans('renatio.dynamicpdf::lang.revisions.empty')) ?></p>
    </div>
<?php else: ?>
    <div class="dynamicpdf-revisions">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th class="revision-date"><?= e(trans('renatio.dynamicpdf::lang.revisions.date')) ?></th>
                    <th class="revision-author"><?= e(trans('renatio.dynamicpdf::lang.revisions.author')) ?></th>
                    <th><?= e(trans('renatio.dynamicpdf::lang.revisions.changes')) ?></th>
                    <th class="revision-action"><span class="visually-hidden"><?= e(trans('renatio.dynamicpdf::lang.templates.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as ['revision' => $revision, 'changes' => $changes]): ?>
                    <?php $date = $revision->createdAtLocal() ?>
                    <tr>
                        <td class="revision-date" title="<?= e($date?->diffForHumans()) ?>">
                            <?= e($date?->format('Y-m-d')) ?>
                            <small><?= e($date?->format('H:i')) ?></small>
                        </td>
                        <td class="revision-author"><?= e($revision->user_name ?: '-') ?></td>
                        <td class="revision-changes">
                            <?php if ($event = $revision->event()): ?>
                                <span class="badge revision-event"><?= e(trans("renatio.dynamicpdf::lang.revisions.event.{$event}")) ?></span>
                            <?php endif ?>
                            <?php $isBaseline = $revision->user_id === null && $revision->user_name === null ?>
                            <?php if ($changes === null && ($isBaseline || count($rows) < Renatio\DynamicPDF\Models\Revision::LIMIT)): ?>
                                <span class="text-muted"><?= e(trans('renatio.dynamicpdf::lang.revisions.' . ($isBaseline ? 'baseline' : 'first'))) ?></span>
                            <?php endif ?>
                            <?php foreach ((array) $changes as $change): ?>
                                <span class="revision-change">
                                    <span class="revision-field"><?= e($change['label']) ?></span>
                                    <?php if ($change['new'] !== null): ?>
                                        <span class="revision-old"><?= e($change['old']) ?></span>
                                        <span class="text-muted" aria-hidden="true">&rarr;</span>
                                        <span class="revision-new"><?= e($change['new']) ?></span>
                                    <?php endif ?>
                                </span>
                            <?php endforeach ?>
                        </td>
                        <td class="revision-action">
                            <?php if ($changes !== null): ?>
                                <a
                                    href="javascript:;"
                                    class="oc-icon-exchange"
                                    title="<?= e(trans('renatio.dynamicpdf::lang.revisions.show_changes')) ?>"
                                    aria-label="<?= e(trans('renatio.dynamicpdf::lang.revisions.show_changes')) ?>"
                                    data-control="popup"
                                    data-handler="onRevisionChanges"
                                    data-request-data="revision: <?= (int) $revision->id ?>"
                                    data-size="giant"
                                ></a>
                            <?php endif ?>
                            <?php if ((int) $revision->id === $currentId): ?>
                                <span class="badge revision-current"><?= e(trans('renatio.dynamicpdf::lang.revisions.current')) ?></span>
                            <?php elseif ($canRestore && $record->restoresChanges($revision, $snapshot)): ?>
                                <a
                                    href="javascript:;"
                                    class="oc-icon-rotate-left"
                                    title="<?= e(trans('renatio.dynamicpdf::lang.revisions.restore')) ?>"
                                    aria-label="<?= e(trans('renatio.dynamicpdf::lang.revisions.restore')) ?>"
                                    data-request="onRestoreRevision"
                                    data-request-data="revision: <?= (int) $revision->id ?>"
                                    data-request-confirm="<?= e(trans('renatio.dynamicpdf::lang.revisions.restore_confirm', ['date' => $revision->createdAtLabel()])) ?>"
                                    data-request-before-update="$(this).trigger('unchange.oc.changeMonitor')"
                                    data-request-message="<?= e(trans('renatio.dynamicpdf::lang.revisions.restoring')) ?>"
                                ></a>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
