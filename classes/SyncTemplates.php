<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Facades\Log;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Throwable;

class SyncTemplates
{
    /** @var array<string, true> */
    protected static array $failed = [];

    /** @var array<string, array<int, string>> */
    protected array $report = ['created' => [], 'deleted' => [], 'failed' => []];

    public function handle(): void
    {
        $this->report = ['created' => [], 'deleted' => [], 'failed' => []];

        $this->createLayouts();

        $registeredTemplates = PDFManager::instance()->listRegisteredTemplates();

        if (! $registeredTemplates) {
            return;
        }

        $dbTemplates = Template::query()->pluck('is_custom', 'code')->all();

        $this->clearNonCustomizedTemplates($dbTemplates, $registeredTemplates);
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
            $this->create($code, function () use ($code): void {
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
     * @param  array<string, string>  $templates
     */
    protected function createTemplates(array $templates): void
    {
        foreach ($templates as $code) {
            $this->create($code, function () use ($code): void {
                $template = new Template;
                $template->fillFromView($code);
                $template->forceSave();
            });
        }
    }

    /**
     * One registered code without a view file must not stop the others from syncing,
     * and a code that keeps failing is logged once per process rather than per request.
     */
    protected function create(string $code, callable $create): void
    {
        if (isset(self::$failed[$code])) {
            $this->report['failed'][] = $code;

            return;
        }

        try {
            $create();
            $this->report['created'][] = $code;
        } catch (Throwable $e) {
            self::$failed[$code] = true;
            $this->report['failed'][] = $code;

            Log::error("Renatio.DynamicPDF could not sync {$code}: {$e->getMessage()}", ['exception' => $e]);
        }
    }
}
