<?php
    $templateVariables = new Renatio\DynamicPDF\Classes\TemplateVariables;
    $isLayout = $formModel instanceof Renatio\DynamicPDF\Models\Layout;
?>
<div data-control="dynamicpdf-variables"
     data-source-field="<?= $isLayout ? '' : 'sample_data' ?>"
     data-target-field="content_html"
     data-variables="<?= e(json_encode($isLayout ? $templateVariables->forLayout($formModel) : $templateVariables->forTemplate($formModel))) ?>"
     data-kinds="<?= e(json_encode(trans('renatio.dynamicpdf::lang.variables.kinds'))) ?>">
    <p class="form-text mb-0"><?= e(trans('renatio.dynamicpdf::lang.variables.comment')) ?></p>
</div>
