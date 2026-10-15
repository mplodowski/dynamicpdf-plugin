<div class="modal-header" data-popup-size="giant">
    <h4 class="modal-title"><?= e(trans('renatio.dynamicpdf::lang.revisions.changes_title', ['date' => $revision->createdAtLabel(), 'author' => $revision->user_name ?: '-'])) ?></h4>
    <button type="button" class="btn-close" data-dismiss="popup"></button>
</div>
<div class="modal-body pb-4">
    <?php if ($isOldest): ?>
        <p class="text-muted mb-0"><?= e(trans('renatio.dynamicpdf::lang.revisions.no_older')) ?></p>
    <?php elseif (! $changes): ?>
        <p class="text-muted mb-0"><?= e(trans('renatio.dynamicpdf::lang.revisions.no_changes')) ?></p>
    <?php endif ?>
    <?php foreach ($changes as $change): ?>
        <section class="dynamicpdf-diff">
            <h5 class="dynamicpdf-diff-field"><?= e($change['label']) ?></h5>
            <div class="dynamicpdf-diff-scroll">
                <table class="dynamicpdf-diff-table">
                    <?php foreach ($change['hunks'] as $index => $hunk): ?>
                        <?php if ($index > 0): ?>
                            <tr class="diff-gap"><td colspan="4" aria-hidden="true">&ctdot;</td></tr>
                        <?php endif ?>
                        <?php foreach ($hunk as $line): ?>
                            <tr class="diff-<?= $line['type'] ?>">
                                <td class="diff-num"><?= $line['old'] ?></td>
                                <td class="diff-num"><?= $line['new'] ?></td>
                                <td class="diff-marker"></td>
                                <td class="diff-text"><?= e($line['text']) ?></td>
                            </tr>
                        <?php endforeach ?>
                    <?php endforeach ?>
                </table>
            </div>
        </section>
    <?php endforeach ?>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="popup">
        <?= e(trans('backend::lang.form.close')) ?>
    </button>
</div>
