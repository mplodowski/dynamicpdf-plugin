<div class="control-tabs content-tabs tabs-flush" data-control="tab">
    <ul class="nav nav-tabs">
        <li class="<?= $activeTab == 'templates' ? 'active' : '' ?>">
            <a href="#templates" data-tab-url="<?= Backend::url('renatio/dynamicpdf/templates/index/templates') ?>">
                <?= e(trans('renatio.dynamicpdf::lang.templates.templates')) ?>
            </a>
        </li>

        <?php if ($canManageLayouts): ?>
            <li class="<?= $activeTab == 'layouts' ? 'active' : '' ?>">
                <a href="#layouts" data-tab-url="<?= Backend::url('renatio/dynamicpdf/templates/index/layouts') ?>">
                    <?= e(trans('renatio.dynamicpdf::lang.templates.layouts')) ?>
                </a>
            </li>
        <?php endif ?>
    </ul>

    <div class="tab-content">
        <div class="tab-pane <?= $activeTab == 'templates' ? 'active' : '' ?>">
            <?= $this->listRender('templates') ?>
        </div>

        <?php if ($canManageLayouts): ?>
            <div class="tab-pane <?= $activeTab == 'layouts' ? 'active' : '' ?>">
                <?= $this->listRender('layouts') ?>
            </div>
        <?php endif ?>
    </div>
</div>