<?php

namespace Renatio\DynamicPDF\Traits;

use Illuminate\Database\UniqueConstraintViolationException;

trait Duplicates
{
    /**
     * A copy with a free code, its attachments and the label of the copy marked as such. The
     * code is looked up again once when a concurrent duplicate took it between check and insert.
     */
    public function duplicate(): static
    {
        $copy = $this->replicateWithRelations();
        $copy->code = $this->uniqueCopyCode((string) $this->code);
        $copy->{$this->duplicateLabelAttribute()} = $this->{$this->duplicateLabelAttribute()} . ' ' . trans('renatio.dynamicpdf::lang.templates.copy_suffix');
        $this->prepareDuplicate($copy);

        try {
            $copy->save();
        } catch (UniqueConstraintViolationException) {
            $copy->code = $this->uniqueCopyCode((string) $this->code);
            $copy->save();
        }

        return $copy;
    }

    abstract protected function duplicateLabelAttribute(): string;

    protected function prepareDuplicate(self $copy): void
    {
    }

    protected function uniqueCopyCode(string $code): string
    {
        $candidate = $code . '_copy';

        for ($i = 2; static::whereCode($candidate)->exists(); $i++) {
            $candidate = "{$code}_copy{$i}";
        }

        return $candidate;
    }
}
