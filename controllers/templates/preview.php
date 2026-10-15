<?php Block::put('breadcrumb') ?>
    <ul>
        <li>
            <a href="<?= e($this->listUrl()) ?>">
                <?= e($this->listLabel('label')) ?>
            </a>
        </li>
        <li><?= e($this->pageTitle) ?></li>
    </ul>
<?php Block::endPut() ?>

<?php if (! $this->fatalError) : ?>
    <?php [$pageWidth, $pageHeight] = $this->previewPageSize($formModel) ?>
    <div class="form-preview" style="overflow-x: auto;">
        <iframe sandbox src="<?= e($this->actionUrl('html', $formModel->id)) ?>"
                title="<?= e($this->pageTitle) ?>"
                style="display: block; margin: 0 auto; width: <?= $pageWidth ?>px; height: <?= $pageHeight ?>px; border: 1px solid #9098a2;"></iframe>
    </div>

    <div class="form-buttons">
        <a class="btn btn-default"
           data-control="dynamicpdf-close-tab"
           href="<?= e($this->formCheckPermission('modelUpdate') ? $this->actionUrl('update', $formModel->id) : $this->listUrl()) ?>">
            <?= e(trans('backend::lang.form.close')) ?>
        </a>
    </div>
<?php else: ?>
    <p class="flash-message static error"><?= e($this->fatalError) ?></p>
    <p>
        <a href="<?= e($this->listUrl()) ?>"
           class="btn btn-default"
           data-control="dynamicpdf-close-tab">
            <?= e($this->listLabel('return')) ?>
        </a>
    </p>
<?php endif ?>
