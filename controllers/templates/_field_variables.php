<?php
    $variables = (new Renatio\DynamicPDF\Classes\TemplateVariables)->forTemplate($formModel);
    $groups = [
        'sample' => 'renatio.dynamicpdf::lang.templates.sample_data',
        'globals' => 'renatio.dynamicpdf::lang.variables.globals',
        'filters' => 'renatio.dynamicpdf::lang.variables.filters',
    ];
?>
<div data-control="dynamicpdf-variables"
     data-copied-text="<?= e(trans('renatio.dynamicpdf::lang.variables.copied')) ?>"
     data-copy-failed-text="<?= e(trans('renatio.dynamicpdf::lang.variables.copy_failed')) ?>">
    <?php foreach ($groups as $group => $heading): ?>
        <h6 class="mt-3 mb-2"><?= e(trans($heading)) ?></h6>

        <?php if ($variables[$group] === null): ?>
            <p class="form-text mt-0"><?= e(trans('renatio.dynamicpdf::lang.variables.sample_data_empty')) ?></p>
        <?php else: ?>
            <ul class="list-unstyled d-flex flex-column gap-1 mb-0">
                <?php foreach ($variables[$group] as $entry): ?>
                    <li class="d-flex flex-wrap align-items-baseline column-gap-2">
                        <button type="button"
                                class="btn btn-sm btn-default font-monospace text-start text-break"
                                data-snippet="<?= e($entry['snippet']) ?>"
                                title="<?= e($entry['snippet']) ?>"
                                aria-label="<?= e(trans('renatio.dynamicpdf::lang.variables.copy', ['snippet' => $entry['snippet']])) ?>"><?= e($entry['label']) ?></button>
                        <?php if ($entry['description']): ?>
                            <span class="text-muted"><?= e($entry['description']) ?></span>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    <?php endforeach ?>
</div>
