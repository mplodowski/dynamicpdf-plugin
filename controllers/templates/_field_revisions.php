<div role="group" aria-labelledby="<?= $field->getId('label') ?>">
    <span class="form-label d-block" id="<?= $field->getId('label') ?>"><?= e(trans('renatio.dynamicpdf::lang.revisions.label')) ?></span>
    <p class="form-text mt-0 mb-3"><?= e(trans('renatio.dynamicpdf::lang.revisions.comment', ['limit' => Renatio\DynamicPDF\Models\Revision::LIMIT])) ?></p>

    <div id="dynamicpdf-revisions"><?= $this->makePartial('revisions', ['record' => $formModel]) ?></div>
</div>
