<div data-control="toolbar">
    <?php if (BackendAuth::userHasAccess('renatio.dynamicpdf.manage_layouts.create')): ?>
        <a href="<?= Backend::url('renatio/dynamicpdf/layouts/create') ?>"
           class="btn btn-primary oc-icon-plus">
            <?= e(trans('renatio.dynamicpdf::lang.templates.new_layout')) ?>
        </a>
    <?php endif ?>
</div>