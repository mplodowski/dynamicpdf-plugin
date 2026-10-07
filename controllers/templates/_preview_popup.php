<div class="modal-header" data-popup-size="giant">
    <h4 class="modal-title"><?= e($title) ?></h4>
    <button type="button" class="btn-close" data-dismiss="popup"></button>
</div>
<div class="modal-body">
    <?php if ($pdf !== null): ?>
        <iframe data-control="dynamicpdf-pdf-preview"
                data-pdf="<?= e($pdf) ?>"
                title="<?= e($title) ?>"
                style="display: block; width: 100%; height: 75vh; border: 1px solid #9098a2;"></iframe>
    <?php else: ?>
        <?php [$pageWidth, $pageHeight] = $pageSize ?>
        <div style="overflow-x: auto;">
            <iframe sandbox
                    srcdoc="<?= e($html) ?>"
                    title="<?= e($title) ?>"
                    style="display: block; margin: 0 auto; width: <?= $pageWidth ?>px; height: <?= $pageHeight ?>px; border: 1px solid #9098a2;"></iframe>
        </div>
    <?php endif ?>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="popup">
        <?= e(trans('backend::lang.form.close')) ?>
    </button>
</div>
