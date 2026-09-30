<?php Block::put('breadcrumb') ?>
    <ul>
        <li>
            <a href="<?= Backend::url('renatio/dynamicpdf/templates/index/layouts') ?>">
                <?= e(trans('renatio.dynamicpdf::lang.layouts.label')) ?>
            </a>
        </li>
        <li><?= e($this->pageTitle) ?></li>
    </ul>
<?php Block::endPut() ?>

<?php if (! $this->fatalError) : ?>
    <?php [$pageWidth, $pageHeight] = $this->previewPageSize($formModel) ?>
    <div class="form-preview" style="overflow-x: auto;">
        <iframe sandbox src="<?= Backend::url('renatio/dynamicpdf/layouts/html/'.$formModel->id) ?>"
                style="display: block; margin: 0 auto; width: <?= $pageWidth ?>px; height: <?= $pageHeight ?>px; border: 1px solid #9098a2;"></iframe>
    </div>

    <div class="form-buttons">
        <a class="btn btn-default"
           href="<?= Backend::url($this->formCheckPermission('modelUpdate') ? 'renatio/dynamicpdf/layouts/update/'.$formModel->id : 'renatio/dynamicpdf/templates/index/layouts') ?>">
            <?= e(trans('backend::lang.form.close')) ?>
        </a>
    </div>
<?php else: ?>
    <p class="flash-message static error"><?= e($this->fatalError) ?></p>
    <p>
        <a href="<?= Backend::url('renatio/dynamicpdf/templates/index/layouts') ?>"
           class="btn btn-default">
            <?= e(trans('renatio.dynamicpdf::lang.layouts.return')) ?>
        </a>
    </p>
<?php endif ?>
