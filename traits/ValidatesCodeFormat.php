<?php

namespace Renatio\DynamicPDF\Traits;

/**
 * Registered views use codes such as author.plugin::pdf.name, and copies append _copy or _copy2.
 * A row stored before the format rule existed keeps its code: the form offers the field only on
 * create, so rejecting it later would block every edit of that row.
 *
 * @mixin \October\Rain\Database\Model
 */
trait ValidatesCodeFormat
{
    public const CODE_FORMAT = 'regex:#^[\w.:/-]+$#';

    protected function exemptStoredCodeFromFormat(): void
    {
        if ($this->exists && ! $this->isDirty('code')) {
            $this->removeValidationRule('code', 'regex');
        }
    }
}
