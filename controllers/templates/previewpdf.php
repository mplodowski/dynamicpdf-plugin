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

<?php if ($this->fatalError) : ?>
    <p class="flash-message static error"><?= e($this->fatalError) ?></p>
    <p>
        <a href="<?= e($this->listUrl()) ?>"
           class="btn btn-default"
           data-control="dynamicpdf-close-tab">
            <?= e($this->listLabel('return')) ?>
        </a>
    </p>
<?php endif ?>