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

<?= $this->formRenderDesign() ?>
