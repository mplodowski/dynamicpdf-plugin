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
    /** @var array<string, true> */
    protected static array $failed = [];

    /** @var array<string, array<int, string>> */
    protected array $report = ['created' => [], 'updated' => [], 'deleted' => [], 'failed' => []];

    public function handle(): void
    {
        $this->report = ['created' => [], 'updated' => [], 'deleted' => [], 'failed' => []];

        $this->createLayouts();

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

    public static function forgetFailures(): void
    {
        self::$failed = [];
    }

    protected function createLayouts(): void
    {
        $registeredLayouts = PDFManager::instance()->listRegisteredLayouts();

        if (! $registeredLayouts) {
            return;
        }

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
        foreach ($dbTemplates as $code => $isCustom) {
            if (! $isCustom && ! array_key_exists($code, $registeredTemplates)) {
                Template::whereCode($code)->delete();
                $this->report['deleted'][] = $code;
            }
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
        if (isset(self::$failed[$code])) {
            $this->report['failed'][] = $code;

            return;
        }

        try {
            if ($write() !== false) {
                $this->report[$outcome][] = $code;
            }
        } catch (UniqueConstraintViolationException) {
        } catch (Throwable $e) {
            self::$failed[$code] = true;
            $this->report['failed'][] = $code;

            Log::error("Renatio.DynamicPDF could not sync {$code}: {$e->getMessage()}", ['exception' => $e]);
        }
    }
}
