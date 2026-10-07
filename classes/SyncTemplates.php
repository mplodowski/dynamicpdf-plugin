<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Throwable;

class SyncTemplates
{
    /** @var array<string, array<int, string>> */
    protected array $report = ['created' => [], 'updated' => [], 'deleted' => [], 'failed' => []];

    public function handle(bool $prune = false): void
    {
        $this->report = ['created' => [], 'updated' => [], 'deleted' => [], 'failed' => []];

        $registeredLayouts = PDFManager::instance()->listRegisteredLayouts();

        if ($registeredLayouts) {
            if (Layout::followsViewUpdates()) {
                $this->refreshLayouts($registeredLayouts);
            }

            $this->createLayouts($registeredLayouts);
        }

        $registeredTemplates = PDFManager::instance()->listRegisteredTemplates();

        if (! $registeredTemplates && ! $prune) {
            return;
        }

        if ($prune) {
            $this->deleteOrphanedTemplates();
        }

        $dbTemplates = Template::query()->pluck('is_custom', 'code')->all();

        if (! $registeredTemplates) {
            return;
        }

        $this->refreshTemplates($registeredTemplates);
        $this->createTemplates(array_diff_key($registeredTemplates, $dbTemplates));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function report(): array
    {
        return $this->report;
    }

    /**
     * @param  array<string, string>  $registeredLayouts
     */
    protected function refreshLayouts(array $registeredLayouts): void
    {
        /** @var Collection<int, Layout> $layouts */
        $layouts = Layout::query()
            ->where('is_locked', true)
            ->whereIn('code', array_keys($registeredLayouts))
            ->get();

        foreach ($layouts as $layout) {
            if ($layout->viewReadFailed()) {
                $this->report['failed'][] = $layout->code;

                continue;
            }

            $this->write(Layout::class, $layout->code, 'updated', fn (): bool => $layout->inDefaultLocale(function () use ($layout): bool {
                if (! $layout->content_html || ! $layout->isDirty(Layout::VIEW_FIELDS)) {
                    return false;
                }

                $layout->forceSave();

                return true;
            }));
        }
    }

    /**
     * @param  array<string, string>  $registeredLayouts
     */
    protected function createLayouts(array $registeredLayouts): void
    {
        $dbLayouts = Layout::query()->pluck('code', 'code')->all();

        foreach (array_diff_key($registeredLayouts, $dbLayouts) as $code) {
            $this->write(Layout::class, $code, 'created', function () use ($code): void {
                $layout = new Layout;
                $layout->is_locked = true;
                $layout->fillFromView($code);
                $layout->forceSave();
            });
        }
    }

    /**
     * @return array<int, string>
     */
    public function orphanedTemplates(): array
    {
        return Template::query()
            ->where('is_custom', false)
            ->whereNotIn('code', array_keys(PDFManager::instance()->listRegisteredTemplates()))
            ->orderBy('code')
            ->pluck('code')
            ->all();
    }

    protected function deleteOrphanedTemplates(): void
    {
        foreach (Template::whereIn('code', $this->orphanedTemplates())->get() as $template) {
            $this->write(Template::class, (string) $template->code, 'deleted', fn (): ?bool => $template->delete());
        }
    }

    /**
     * A view file read mid-deploy can parse to no content or a layout that does not resolve
     * yet; neither may wipe the stored row.
     *
     * @param  array<string, string>  $registeredTemplates
     */
    protected function refreshTemplates(array $registeredTemplates): void
    {
        /** @var Collection<int, Template> $templates */
        $templates = Template::query()
            ->where('is_custom', false)
            ->whereIn('code', array_keys($registeredTemplates))
            ->get();

        foreach ($templates as $template) {
            if ($template->viewReadFailed()) {
                $this->report['failed'][] = $template->code;

                continue;
            }

            $this->write(Template::class, $template->code, 'updated', fn (): bool => $template->inDefaultLocale(function () use ($template): bool {
                if (! $template->content_html || ! $template->isDirty(Template::VIEW_FIELDS)) {
                    return false;
                }

                if ($template->layout_id === null && $template->getOriginal('layout_id') !== null) {
                    return false;
                }

                $template->forceSave();

                return true;
            }));
        }
    }

    /**
     * @param  array<string, string>  $templates
     */
    protected function createTemplates(array $templates): void
    {
        foreach ($templates as $code) {
            $this->write(Template::class, $code, 'created', function () use ($code): void {
                $template = new Template;
                $template->fillFromView($code);
                $template->forceSave();
            });
        }
    }

    /**
     * @param  class-string  $model
     */
    protected function write(string $model, string $code, string $outcome, callable $write): void
    {
        try {
            if ($write() !== false) {
                $this->report[$outcome][] = $code;
            }
        } catch (UniqueConstraintViolationException) {
        } catch (Throwable $e) {
            $this->report['failed'][] = $code;

            PDFManager::instance()->logFailureOnce($model, $code, "Renatio.DynamicPDF could not sync {$code}: {$e->getMessage()}", $e);
        }
    }
}
