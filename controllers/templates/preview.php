<?php Block::put('breadcrumb') ?>
    <ul>
        <li>
            <a href="<?= Backend::url('renatio/dynamicpdf/templates') ?>">
                <?= e(trans('renatio.dynamicpdf::lang.templates.label')) ?>
            </a>
        </li>
        <li><?= e($this->pageTitle) ?></li>
    </ul>
<?php Block::endPut() ?>

<?php if (! $this->fatalError) : ?>
    <?php [$pageWidth, $pageHeight] = $this->previewPageSize($formModel) ?>
    <div class="form-preview" style="overflow-x: auto;">
        <iframe sandbox src="<?= Backend::url('renatio/dynamicpdf/templates/html/'.$formModel->id) ?>"
                title="<?= e($this->pageTitle) ?>"
                style="display: block; margin: 0 auto; width: <?= $pageWidth ?>px; height: <?= $pageHeight ?>px; border: 1px solid #9098a2;"></iframe>
    </div>

    <div class="form-buttons">
        <a class="btn btn-default"
           data-control="dynamicpdf-close-tab"
           href="<?= Backend::url($this->formCheckPermission('modelUpdate') ? 'renatio/dynamicpdf/templates/update/'.$formModel->id : 'renatio/dynamicpdf/templates') ?>">
            <?= e(trans('backend::lang.form.close')) ?>
        </a>
    </div>
<?php else: ?>
    <p class="flash-message static error"><?= e($this->fatalError) ?></p>
    <p>
        <a href="<?= Backend::url('renatio/dynamicpdf/templates') ?>"
           class="btn btn-default"
           data-control="dynamicpdf-close-tab">
            <?= e(trans('renatio.dynamicpdf::lang.templates.return')) ?>
        </a>
    </p>
<?php endif ?>
