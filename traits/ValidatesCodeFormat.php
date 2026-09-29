<?php

namespace Renatio\DynamicPDF\Traits;

/**
 * A code stored before the format rule existed is exempt: the form offers the field only on
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
