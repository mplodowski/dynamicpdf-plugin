<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Throwable;

class SyncTemplates
{
    /** @var array<string, array<int, string>> */
    protected array $report = ['created' => [], 'updated' => [], 'deleted' => [], 'failed' => []];

    public function handle(): void
    {
        $this->report = ['created' => [], 'updated' => [], 'deleted' => [], 'failed' => []];

        $registeredLayouts = PDFManager::instance()->listRegisteredLayouts();

        if ($registeredLayouts) {
            $this->refreshLayouts($registeredLayouts);
            $this->createLayouts($registeredLayouts);
        }

        $registeredTemplates = PDFManager::instance()->listRegisteredTemplates();

        if (! $registeredTemplates) {
            return;
        }

        $dbTemplates = Template::query()->pluck('is_custom', 'code')->all();

        $this->clearNonCustomizedTemplates($dbTemplates, $registeredTemplates);
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
            $this->write($layout->code, 'updated', fn (): bool => $layout->inDefaultLocale(function () use ($layout): bool {
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
            $this->write($code, 'created', function () use ($code): void {
                $layout = new Layout;
                $layout->is_locked = true;
                $layout->fillFromView($code);
                $layout->forceSave();
            });
        }
    }

    /**
     * @param  array<string, bool>  $dbTemplates
     * @param  array<string, string>  $registeredTemplates
     */
    protected function clearNonCustomizedTemplates(array $dbTemplates, array $registeredTemplates): void
    {
        $obsolete = array_keys(array_diff_key(array_filter($dbTemplates, fn (mixed $isCustom): bool => ! $isCustom), $registeredTemplates));

        if ($obsolete === []) {
            return;
        }

        Template::whereIn('code', $obsolete)->delete();
        $this->report['deleted'] = $obsolete;
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
            $this->write($template->code, 'updated', fn (): bool => $template->inDefaultLocale(function () use ($template): bool {
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
            $this->write($code, 'created', function () use ($code): void {
                $template = new Template;
                $template->fillFromView($code);
                $template->forceSave();
            });
        }
    }

    protected function write(string $code, string $outcome, callable $write): void
    {
        try {
            if ($write() !== false) {
                $this->report[$outcome][] = $code;
            }
        } catch (UniqueConstraintViolationException) {
            // Another request synced the same code a moment earlier; the row exists.
        } catch (Throwable $e) {
            $this->report['failed'][] = $code;

            Log::error("Renatio.DynamicPDF could not sync {$code}: {$e->getMessage()}", ['exception' => $e]);
        }
    }
}
