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
     * The form data reaches the model only after formBeforeSave, so the posted values are compared.
     *
     * @param  array<string, string>  $fields
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
