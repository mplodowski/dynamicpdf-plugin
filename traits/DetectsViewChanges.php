<?php

namespace Renatio\DynamicPDF\Traits;

use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

/**
 * @mixin \Backend\Classes\Controller
 */
trait DetectsViewChanges
{
    /**
     * The posted values are compared with the model because the form data is applied to it
     * only after formBeforeSave. A form editing a translation writes translated fields to the
     * translation row, so only a change to a shared field counts.
     *
     * @param  array<string, string>  $fields  form field => model attribute
     */
    protected function postedViewFieldChanged(Layout|Template $model, array $fields): bool
    {
        $widget = $this->formGetWidget();

        if ($widget === null) {
            return false;
        }

        $posted = (array) $widget->getSaveData();

        if ($model->shouldTranslate()) {
            $fields = array_diff_key($fields, array_flip($model->getTranslatableAttributes()));
        }

        foreach ($fields as $field => $attribute) {
            if (array_key_exists($field, $posted) && $this->normalizeViewValue($posted[$field]) !== $this->normalizeViewValue($model->getAttribute($attribute))) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeViewValue(mixed $value): string
    {
        return str_replace("\r\n", "\n", trim((string) $value));
    }
}
