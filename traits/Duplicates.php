<?php

namespace Renatio\DynamicPDF\Traits;

trait Duplicates
{
    /**
     * A copy with a free code, its attachments and the label of the copy marked as such.
     */
    public function duplicate(): static
    {
        $copy = $this->replicateWithRelations();
        $copy->code = $this->uniqueCopyCode((string) $this->code);
        $copy->{$this->duplicateLabelAttribute()} = $this->{$this->duplicateLabelAttribute()} . ' ' . trans('renatio.dynamicpdf::lang.templates.copy_suffix');
        $this->prepareDuplicate($copy);
        $copy->save();

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
