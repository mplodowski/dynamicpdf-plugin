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
    <?= Form::open(['class' => 'layout']) ?>

    <div class="layout-row">
        <?= $this->formRender() ?>
    </div>

    <div class="form-buttons pt-4">
        <div class="loading-indicator-container">
            <button type="submit"
                    data-request="onSave"
                    data-request-data="redirect:0"
                    data-hotkey="ctrl+s, cmd+s"
                    data-load-indicator="<?= e(trans('backend::lang.form.saving_name', ['name' => $formRecordName])) ?>"
                    class="btn btn-primary">
                <?= e(trans('backend::lang.form.save')) ?>
            </button>

            <button type="button"
                    data-request="onSave"
                    data-request-data="close:1"
                    data-hotkey="ctrl+enter, cmd+enter"
                    data-load-indicator="<?= e(trans('backend::lang.form.saving_name', ['name' => $formRecordName])) ?>"
                    class="btn btn-default">
                <?= e(trans('backend::lang.form.save_and_close')) ?>
            </button>

            <a class="btn btn-info"
               href="<?= Backend::url('renatio/dynamicpdf/templates/preview/'.$formModel->id) ?>">
                <?= e(trans('renatio.dynamicpdf::lang.templates.preview_html')) ?>
            </a>

            <a class="btn btn-info"
               target="_blank"
               href="<?= Backend::url('renatio/dynamicpdf/templates/previewpdf/'.$formModel->id) ?>">
                <?= e(trans('renatio.dynamicpdf::lang.templates.preview_pdf')) ?>
            </a>

            <?php if ($formModel->getView()): ?>
                <button type="button"
                        class="btn btn-danger pull-right"
                        data-request="onResetDefault"
                        data-load-indicator="<?= e(trans('backend::lang.form.resetting')) ?>"
                        data-request-confirm="<?= e(trans('backend::lang.form.action_confirm')) ?>">
                    <?= e(trans('backend::lang.form.reset_default')) ?>
                </button>
            <?php else : ?>
                <button type="button"
                        class="oc-icon-trash-o btn-icon danger pull-right"
                        data-request="onDelete"
                        data-load-indicator="<?= e(trans('backend::lang.form.deleting_name',
                            ['name' => $formRecordName])) ?>"
                        data-request-confirm="<?= e(trans('backend::lang.form.action_confirm')) ?>">
                </button>
            <?php endif ?>

            <span class="btn-text">
                <?= e(trans('backend::lang.form.or')) ?>
                <a href="<?= Backend::url('renatio/dynamicpdf/templates') ?>">
                    <?= e(trans('backend::lang.form.cancel')) ?>
                </a>
            </span>
        </div>
    </div>

    <?= Form::close() ?>
<?php else: ?>
    <p class="flash-message static error"><?= e($this->fatalError) ?></p>
    <p>
        <a href="<?= Backend::url('renatio/dynamicpdf/templates') ?>"
           class="btn btn-default">
            <?= e(trans('renatio.dynamicpdf::lang.templates.return')) ?>
        </a>
    </p>
<?php endif ?>
