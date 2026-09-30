<?php

namespace Renatio\DynamicPDF\Traits;

use Illuminate\Database\UniqueConstraintViolationException;

trait Duplicates
{
    public function duplicate(): static
    {
        $copy = $this->replicateWithRelations();
        $copy->code = $this->uniqueCopyCode((string) $this->code);
        $copy->{$this->labelAttribute()} = $this->{$this->labelAttribute()} . ' ' . trans('renatio.dynamicpdf::lang.templates.copy_suffix');
        $this->prepareDuplicate($copy);

        try {
            $copy->save();
        } catch (UniqueConstraintViolationException) {
            $copy->code = $this->uniqueCopyCode((string) $this->code);
            $copy->save();
        }

        return $copy;
    }

    abstract public function labelAttribute(): string;

    protected function prepareDuplicate(self $copy): void
    {
    }

    protected function uniqueCopyCode(string $code): string
    {
        $code = (string) preg_replace('#[^\w.:/-]#', '_', $code);
        $candidate = $code . '_copy';

        for ($i = 2; static::whereCode($candidate)->exists(); $i++) {
            $candidate = "{$code}_copy{$i}";
        }

        return $candidate;
    }
}
