<?php

namespace Renatio\DynamicPDF\Traits;

trait DescribesViewStatus
{
    abstract public function followsView(): bool;

    abstract public function isCustomised(): bool;

    abstract public function getView(): ?string;

    /**
     * @return bool
     */
    abstract public function shouldTranslate();

    /**
     * @param  object  $fields
     */
    protected function describeViewStatus($fields): void
    {
        if (! isset($fields->_view_status)) {
            return;
        }

        if (! $this->followsView()) {
            $fields->_view_status->hidden();

            return;
        }

        $customised = $this->isCustomised();
        $group = strtolower(class_basename(static::class));
        $key = match (true) {
            $customised => 'view_detached',
            $this->shouldTranslate() => 'view_follows_translation',
            default => 'view_follows',
        };

        $fields->_view_status
            ->mode($customised ? 'warning' : 'info')
            ->comment(trans("renatio.dynamicpdf::lang.{$group}.{$key}", [
                'view' => $this->getView(),
                'reset' => trans('backend::lang.form.reset_default'),
            ]));
    }
}
