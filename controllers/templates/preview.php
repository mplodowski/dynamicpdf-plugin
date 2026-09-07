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
    <div class="form-preview" style="display: flex; justify-content: center;">
        <iframe src="<?= Backend::url('renatio/dynamicpdf/templates/html/'.$formModel->id) ?>"
                style="width: 793px; height: 1121px; border: 1px solid #9098a2;"></iframe>
    </div>

    <div class="form-buttons">
        <a class="btn btn-default"
           href="<?= Backend::url('renatio/dynamicpdf/templates/update/'.$formModel->id) ?>">
            <?= e(trans('backend::lang.form.close')) ?>
        </a>
    </div>
<?php else: ?>
    <p class="flash-message static error"><?= e($this->fatalError) ?></p>
    <p>
        <a href="<?= Backend::url('renatio/dynamicpdf/templates') ?>"
           class="btn btn-default">
            <?= e(trans('renatio.dynamicpdf::lang.templates.return')) ?>
        </a>
    </p>
<?php endif ?>
